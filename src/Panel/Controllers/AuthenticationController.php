<?php

namespace Formwork\Panel\Controllers;

use Formwork\Authentication\Authenticator;
use Formwork\Authentication\Exceptions\AuthenticationFailedException;
use Formwork\Authentication\Exceptions\RateLimitExceededException;
use Formwork\Authentication\Exceptions\UserNotLoggedException;
use Formwork\Http\RedirectResponse;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Events\PanelLoggedInEvent;
use Formwork\Panel\Events\PanelLoggedOutEvent;
use Formwork\Schemes\Schemes;

final class AuthenticationController extends AbstractController
{
    /**
     * Session key used to store the URI to redirect to after login
     */
    public const string SESSION_REDIRECT_KEY = '_formwork_redirect_to';

    /**
     * Authentication@login action
     */
    public function login(Schemes $schemes, Authenticator $authenticator): Response
    {
        if ($this->panel->isLoggedIn()) {
            return $this->redirect($this->generateRoute('panel.index'));
        }

        $fields = $schemes->get('forms.login')->fields();

        if ($this->request->method() === RequestMethod::POST) {
            $form = $this->form('login', $fields)
                ->processRequest($this->request);

            if (!$form->isValid()) {
                return $this->error(
                    $this->translate('panel.login.attempt.failed'),
                    ['fields' => $form->fields()]
                );
            }

            try {
                $user = $authenticator->login($form->data()->get('login'), $form->data()->get('password'));

                // Regenerate CSRF token
                $this->csrfToken->generate($this->panel->getCsrfTokenName());

                $this->events->dispatch(new PanelLoggedInEvent($user, $this->request));

                if (($destination = $this->request->session()->get(self::SESSION_REDIRECT_KEY)) !== null) {
                    $this->request->session()->remove(self::SESSION_REDIRECT_KEY);
                    return new RedirectResponse($this->panel->uri($destination));
                }

                return $this->redirect($this->generateRoute('panel.index'));
            } catch (RateLimitExceededException $e) {
                // Regenerate CSRF token
                $this->csrfToken->generate($this->panel->getCsrfTokenName());

                return $this->error(
                    $this->translate('panel.login.attempt.tooMany', round($e->getResetTime() / 60)),
                    ['fields' => $fields],
                    ResponseStatus::TooManyRequests,
                    ['Retry-After' => (string) $e->getResetTime()]
                );
            } catch (AuthenticationFailedException) {
                return $this->error(
                    $this->translate('panel.login.attempt.failed'),
                    ['fields' => $fields]
                );
            }
        }

        return new Response($this->view('@panel.authentication.login', [
            'title'  => $this->translate('panel.login.login'),
            'fields' => $fields,
        ]));
    }

    /**
     * Authentication@logout action
     */
    public function logout(): RedirectResponse
    {
        try {
            $user = $this->panel->user();
            $user->logout();
            $this->csrfToken->destroy($this->panel->getCsrfTokenName());

            $this->events->dispatch(new PanelLoggedOutEvent($user));

            if ($this->config->getString('system.panel.logoutRedirect') === 'home') {
                return $this->redirect('/');
            }

            $this->panel->notify($this->translate('panel.login.loggedOut'), 'info');
        } catch (UserNotLoggedException) {
            // Do nothing if user is not logged in, the user will be redirected to the login page
        }

        return $this->redirect($this->generateRoute('panel.index'));
    }

    /**
     * Display login view with an error notification
     *
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     */
    private function error(string $message, array $data = [], ResponseStatus $responseStatus = ResponseStatus::OK, array $headers = []): Response
    {
        $defaults = ['title' => $this->translate('panel.login.login'), 'error' => true];
        $this->panel->notify($message, 'error');
        return new Response($this->view('@panel.authentication.login', [...$defaults, ...$data]), $responseStatus, $headers);
    }
}
