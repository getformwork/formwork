<?php

namespace Formwork\Panel\Controllers;

use Formwork\Http\RedirectResponse;
use Formwork\Http\Request;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Log\Log;
use Formwork\Log\Registry;
use Formwork\Panel\Security\AccessLimiter;
use Formwork\Security\CsrfToken;
use Formwork\Utils\FileSystem;
use RuntimeException;

class AuthenticationController extends AbstractController
{
    /**
     * Authentication@login action
     */
    public function login(Request $request, CsrfToken $csrfToken, AccessLimiter $accessLimiter): Response
    {
        if ($accessLimiter->hasReachedLimit()) {
            $minutes = round($this->config->get('system.panel.loginResetTime') / 60);
            $csrfToken->generate();
            return $this->error($this->translate('panel.login.attempt.tooMany', $minutes));
        }

        switch ($request->method()) {
            case RequestMethod::GET:
                if ($request->session()->has('FORMWORK_USERNAME')) {
                    return $this->redirect($this->generateRoute('panel.index'));
                }

                // Always generate a new CSRF token
                $csrfToken->generate();

                return new Response($this->view('authentication.login', [
                    'title' => $this->translate('panel.login.login'),
                ]));

            case RequestMethod::POST:
                // Delay request processing for 0.5-1s
                usleep(random_int(500, 1000) * 1000);

                $data = $request->input();

                // Ensure no required data is missing
                if (!$data->hasMultiple(['username', 'password'])) {
                    $csrfToken->generate();
                    $this->error($this->translate('panel.login.attempt.failed'));
                }

                $accessLimiter->registerAttempt();

                $user = $this->panel()->users()->get($data->get('username'));

                // Authenticate user
                if ($user !== null && $user->authenticate($data->get('password'))) {
                    $request->session()->regenerate();
                    $request->session()->set('FORMWORK_USERNAME', $data->get('username'));

                    // Regenerate CSRF token
                    $csrfToken->generate();

                    $accessLog = new Log(FileSystem::joinPaths($this->config->get('system.panel.paths.logs'), 'access.json'));
                    $lastAccessRegistry = new Registry(FileSystem::joinPaths($this->config->get('system.panel.paths.logs'), 'lastAccess.json'));

                    $time = $accessLog->log($data->get('username'));
                    $lastAccessRegistry->set($data->get('username'), $time);

                    $accessLimiter->resetAttempts();

                    if (($destination = $request->session()->get('FORMWORK_REDIRECT_TO')) !== null) {
                        $request->session()->remove('FORMWORK_REDIRECT_TO');
                        return new RedirectResponse($this->panel->uri($destination));
                    }

                    return $this->redirect($this->generateRoute('panel.index'));
                }

                $csrfToken->generate();
                return $this->error($this->translate('panel.login.attempt.failed'), [
                    'username' => $data->get('username'),
                    'error'    => true,
                ]);
        }

        throw new  RuntimeException('Invalid Method');
    }

    /**
     * Authentication@logout action
     */
    public function logout(Request $request, CsrfToken $csrfToken): RedirectResponse
    {
        $csrfToken->destroy();
        $request->session()->remove('FORMWORK_USERNAME');
        $request->session()->destroy();

        if ($this->config->get('system.panel.logoutRedirect') === 'home') {
            return $this->redirect('/');
        }
        $this->panel()->notify($this->translate('panel.login.loggedOut'), 'info');
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
