<?php

namespace Formwork\Panel\Controllers;

use Formwork\Formwork;
use Formwork\Panel\Security\AccessLimiter;
use Formwork\Panel\Security\CSRFToken;
use Formwork\Response\RedirectResponse;
use Formwork\Response\Response;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Log;
use Formwork\Utils\Registry;
use Formwork\Utils\Session;

class AuthenticationController extends AbstractController
{
    /**
     * Authentication@login action
     */
    public function login(): Response
    {
        $attemptsRegistry = new Registry(Formwork::instance()->config()->get('panel.paths.logs') . 'accessAttempts.json');

        $limiter = new AccessLimiter(
            $attemptsRegistry,
            Formwork::instance()->config()->get('panel.loginAttempts'),
            Formwork::instance()->config()->get('panel.loginResetTime')
        );

        if ($limiter->hasReachedLimit()) {
            $minutes = round(Formwork::instance()->config()->get('panel.loginResetTime') / 60);
            return $this->error($this->translate('panel.login.attempt.tooMany', $minutes));
        }

        switch (HTTPRequest::method()) {
            case 'GET':
                if (Session::has('FORMWORK_USERNAME')) {
                    return $this->redirectToPanel();
                }

                // Always generate a new CSRF token
                CSRFToken::generate();

                return new Response($this->view('authentication.login', [
                    'title' => $this->translate('panel.login.login'),
                ], true));

                break;

            case 'POST':
                // Delay request processing for 0.5-1s
                usleep(random_int(500, 1000) * 1e3);

                $data = HTTPRequest::postData();

                // Ensure no required data is missing
                if (!$data->hasMultiple(['username', 'password'])) {
                    $this->error($this->translate('panel.login.attempt.failed'));
                }

                $limiter->registerAttempt();

                $user = $this->panel()->users()->get($data->get('username'));

                // Authenticate user
                if ($user !== null && $user->authenticate($data->get('password'))) {
                    // Regenerate session id
                    Session::regenerate();

                    Session::set('FORMWORK_USERNAME', $data->get('username'));

                    // Regenerate CSRF token
                    CSRFToken::generate();

                    $accessLog = new Log(Formwork::instance()->config()->get('panel.paths.logs') . 'access.json');
                    $lastAccessRegistry = new Registry(Formwork::instance()->config()->get('panel.paths.logs') . 'lastAccess.json');

                    $time = $accessLog->log($data->get('username'));
                    $lastAccessRegistry->set($data->get('username'), $time);

                    $limiter->resetAttempts();

                    if (($destination = Session::get('FORMWORK_REDIRECT_TO')) !== null) {
                        Session::remove('FORMWORK_REDIRECT_TO');
                        return $this->redirect($destination);
                    }

                    return $this->redirectToPanel();
                }

                return $this->error($this->translate('panel.login.attempt.failed'), [
                    'username' => $data->get('username'),
                    'error'    => true,
                ]);

                break;
        }
    }

    /**
     * Authentication@logout action
     */
    public function logout(): RedirectResponse
    {
        CSRFToken::destroy();
        Session::remove('FORMWORK_USERNAME');
        Session::destroy();

        if (Formwork::instance()->config()->get('panel.logoutRedirect') === 'home') {
            return $this->redirectToSite();
        }
        $this->panel()->notify($this->translate('panel.login.loggedOut'), 'info');
        return $this->redirectToPanel();
    }

    /**
     * Display login view with an error notification
     *
     * @param string $message Error message
     * @param array  $data    Data to pass to the view
     */
    protected function error(string $message, array $data = []): Response
    {
        // Ensure CSRF token is re-generated
        CSRFToken::generate();

        $defaults = ['title' => $this->translate('panel.login.login')];
        $this->panel()->notify($message, 'error');
        return new Response($this->view('authentication.login', array_merge($defaults, $data), true));
    }
}
