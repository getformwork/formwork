<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Security\AccessLimiter;
use Formwork\Admin\Security\CSRFToken;
use Formwork\Formwork;
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
        $attemptsRegistry = new Registry(Formwork::instance()->config()->get('admin.paths.logs') . 'accessAttempts.json');

        $limiter = new AccessLimiter(
            $attemptsRegistry,
            Formwork::instance()->config()->get('admin.login_attempts'),
            Formwork::instance()->config()->get('admin.login_reset_time')
        );

        if ($limiter->hasReachedLimit()) {
            $minutes = round(Formwork::instance()->config()->get('admin.login_reset_time') / 60);
            return $this->error($this->admin()->translate('admin.login.attempt.too-many', $minutes));
        }

        switch (HTTPRequest::method()) {
            case 'GET':
                if (Session::has('FORMWORK_USERNAME')) {
                    $this->admin()->redirectToPanel();
                }

                // Always generate a new CSRF token
                CSRFToken::generate();

                return new Response($this->view('authentication.login', [
                    'title' => $this->admin()->translate('admin.login.login')
                ], true));

                break;

            case 'POST':
                // Delay request processing for 0.5-1s
                usleep(random_int(500, 1000) * 1e3);

                $data = HTTPRequest::postData();

                // Ensure no required data is missing
                if (!$data->hasMultiple(['username', 'password'])) {
                    $this->error($this->admin()->translate('admin.login.attempt.failed'));
                }

                $limiter->registerAttempt();

                $user = $this->admin()->users()->get($data->get('username'));

                // Authenticate user
                if ($user !== null && $user->authenticate($data->get('password'))) {
                    Session::set('FORMWORK_USERNAME', $data->get('username'));

                    // Regenerate CSRF token
                    CSRFToken::generate();

                    $accessLog = new Log(Formwork::instance()->config()->get('admin.paths.logs') . 'access.json');
                    $lastAccessRegistry = new Registry(Formwork::instance()->config()->get('admin.paths.logs') . 'lastAccess.json');

                    $time = $accessLog->log($data->get('username'));
                    $lastAccessRegistry->set($data->get('username'), $time);

                    $limiter->resetAttempts();

                    if (($destination = Session::get('FORMWORK_REDIRECT_TO')) !== null) {
                        Session::remove('FORMWORK_REDIRECT_TO');
                        $this->admin()->redirect($destination);
                    }

                    $this->admin()->redirectToPanel();
                }

                return $this->error($this->admin()->translate('admin.login.attempt.failed'), [
                    'username' => $data->get('username'),
                    'error'    => true
                ]);

                break;
        }
    }

    /**
     * Authentication@logout action
     */
    public function logout(): void
    {
        CSRFToken::destroy();
        Session::remove('FORMWORK_USERNAME');
        Session::destroy();

        if (Formwork::instance()->config()->get('admin.logout_redirect') === 'home') {
            $this->admin()->redirectToSite();
        } else {
            $this->admin()->notify($this->admin()->translate('admin.login.logged-out'), 'info');
            $this->admin()->redirectToPanel();
        }
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

        $defaults = ['title' => $this->admin()->translate('admin.login.login')];
        $this->admin()->notify($message, 'error');
        return new Response($this->view('authentication.login', array_merge($defaults, $data), true));
    }
}
