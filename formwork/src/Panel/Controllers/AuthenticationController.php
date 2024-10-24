<?php

namespace Formwork\Panel\Controllers;

use Formwork\Http\RedirectResponse;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Log\Log;
use Formwork\Log\Registry;
use Formwork\Panel\Security\AccessLimiter;
use Formwork\Users\Exceptions\AuthenticationFailedException;
use Formwork\Users\Exceptions\UserNotLoggedException;
use Formwork\Users\User;
use Formwork\Utils\FileSystem;

class AuthenticationController extends AbstractController
{
    public const SESSION_REDIRECT_KEY = '_formwork_redirect_to';

    /**
     * Authentication@login action
     */
    public function login(AccessLimiter $accessLimiter): Response
    {
        if ($this->panel()->isLoggedIn()) {
            return $this->redirect($this->generateRoute('panel.index'));
        }

        $csrfTokenName = $this->panel()->getCsrfTokenName();

        if ($accessLimiter->hasReachedLimit()) {
            $minutes = round($this->config->get('system.panel.loginResetTime') / 60);
            $this->csrfToken->generate($csrfTokenName);
            return $this->error($this->translate('panel.login.attempt.tooMany', $minutes));
        }

        if ($this->request->method() === RequestMethod::POST) {
            // Delay request processing for 0.5-1s
            usleep(random_int(500, 1000) * 1000);

            $data = $this->request->input();

            // Ensure no required data is missing
            if (!$data->hasMultiple(['username', 'password'])) {
                $this->csrfToken->generate($csrfTokenName);
                $this->error($this->translate('panel.login.attempt.failed'));
            }

            $accessLimiter->registerAttempt();

            $username = $data->get('username');

            /** @var User */
            $user = $this->site->users()->get($username);

            // Authenticate user
            if ($user !== null) {
                try {
                    $user->authenticate($data->get('password'));

                    // Regenerate CSRF token
                    $this->csrfToken->generate($csrfTokenName);

                    $accessLog = new Log(FileSystem::joinPaths($this->config->get('system.panel.paths.logs'), 'access.json'));
                    $lastAccessRegistry = new Registry(FileSystem::joinPaths($this->config->get('system.panel.paths.logs'), 'lastAccess.json'));

                    $time = $accessLog->log($username);
                    $lastAccessRegistry->set($username, $time);

                    $accessLimiter->resetAttempts();

                    if (($destination = $this->request->session()->get(self::SESSION_REDIRECT_KEY)) !== null) {
                        $this->request->session()->remove(self::SESSION_REDIRECT_KEY);
                        return new RedirectResponse($this->panel->uri($destination));
                    }

                    return $this->redirect($this->generateRoute('panel.index'));
                } catch (AuthenticationFailedException) {
                    // Do nothing, the error response will be sent below
                }
            }

            $this->csrfToken->generate($csrfTokenName);

            return $this->error($this->translate('panel.login.attempt.failed'), [
                'username' => $username,
                'error'    => true,
            ]);
        }

        // Always generate a new CSRF token
        $this->csrfToken->generate($csrfTokenName);

        return new Response($this->view('authentication.login', [
            'title' => $this->translate('panel.login.login'),
        ]));
    }

    /**
     * Authentication@logout action
     */
    public function logout(): RedirectResponse
    {
        try {
            $this->panel->user()->logout();
            $this->csrfToken->destroy($this->panel()->getCsrfTokenName());

            if ($this->config->get('system.panel.logoutRedirect') === 'home') {
                return $this->redirect('/');
            }

            $this->panel()->notify($this->translate('panel.login.loggedOut'), 'info');
        } catch (UserNotLoggedException) {
            // Do nothing if user is not logged, the user will be redirected to the login page
        }

        return $this->redirect($this->generateRoute('panel.index'));
    }

    /**
     * Display login view with an error notification
     *
     * @param array<string, mixed> $data
     */
    protected function error(string $message, array $data = []): Response
    {
        $defaults = ['title' => $this->translate('panel.login.login')];
        $this->panel()->notify($message, 'error');
        return new Response($this->view('authentication.login', [...$defaults, ...$data]));
    }
}
