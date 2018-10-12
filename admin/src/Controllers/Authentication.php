<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\Security\AccessLimiter;
use Formwork\Admin\Security\CSRFToken;
use Formwork\Admin\Utils\Session;
use Formwork\Data\DataGetter;
use Formwork\Utils\HTTPRequest;

class Authentication extends AbstractController
{
    public function login()
    {
        $limiter = new AccessLimiter(
            $this->registry('accessAttempts'),
            $this->option('admin.login_attempts'),
            $this->option('admin.login_reset_time')
        );

        if ($limiter->hasReachedLimit()) {
            $minutes = round($this->option('admin.login_reset_time') / 60);
            $this->error($this->label('login.attempt.too-many', $minutes));
            return;
        }

        switch (HTTPRequest::method()) {
            case 'GET':
                if (Session::has('FORMWORK_USERNAME')) {
                    $this->redirectToPanel(302, true);
                }

                if (is_null(CSRFToken::get())) {
                    CSRFToken::generate();
                }

                $this->view('authentication.login', array(
                    'title' => $this->label('login.login')
                ));

                break;

            case 'POST':
                usleep(rand(500, 1000) * 1e3);

                $data = new DataGetter(HTTPRequest::postData());

                if (!$data->has(array('username', 'password'))) {
                    return $this->error();
                }

                $limiter->registerAttempt();

                $user = Admin::instance()->users()->get($data->get('username'));

                if (!is_null($user) && $user->authenticate($data->get('password'))) {
                    Session::set('FORMWORK_USERNAME', $data->get('username'));

                    $time = $this->log('access')->log($data->get('username'));
                    $this->registry('lastAccess')->set($data->get('username'), $time);

                    $limiter->resetAttempts();

                    if (!is_null($destination = Session::get('FORMWORK_REDIRECT_TO'))) {
                        Session::remove('FORMWORK_REDIRECT_TO');
                        $this->redirect($destination, 302, true);
                    }

                    $this->redirectToPanel(302, true);
                }

                $this->error($this->label('login.attempt.failed'), array(
                    'username' => $data->get('username'),
                    'error' => true
                ));

                break;
        }
    }

    public function logout()
    {
        CSRFToken::destroy();
        Session::remove('FORMWORK_USERNAME');
        Session::destroy();

        if ($this->option('admin.logout_redirect') === 'home') {
            $this->redirectToSite(302, true);
        } else {
            $this->notify($this->label('login.logged-out'), 'info');
            $this->redirectToPanel(302, true);
        }
    }

    protected function error($message, $data = array())
    {
        $defaults = array('title' => $this->label('login.login'));
        $this->notify($message, 'error');
        $this->view('authentication.login', array_merge($defaults, $data));
    }
}
