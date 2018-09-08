<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\Security\CSRFToken;
use Formwork\Admin\Utils\Session;
use Formwork\Utils\HTTPRequest;

class Authentication extends AbstractController
{
    protected $username;

    protected $password;

    public function login()
    {
        switch (HTTPRequest::method()) {
            case 'GET':
                if (Session::has('FORMWORK_USERNAME')) {
                    $this->redirect('/', 302, true);
                }
                if (is_null(CSRFToken::get())) {
                    CSRFToken::generate();
                }
                $this->view('authentication.login', array(
                    'title' => $this->label('login.login'),
                    'csrfToken' => CSRFToken::get()
                ));
                break;

            case 'POST':
                usleep(rand(500, 1000) * 1e3);

                $users = Admin::instance()->users();

                $postData = HTTPRequest::postData();

                foreach (array('username', 'password') as $var) {
                    if (!isset($postData[$var])) {
                        return $this->error();
                    }
                    $this->$var = $postData[$var];
                }

                if ($users->has($this->username) && $users->get($this->username)->authenticate($this->password)) {
                    Session::set('FORMWORK_USERNAME', $this->username);
                    $time = $this->log('access')->set($this->username);
                    $this->registry('lastAccess')->set($this->username, $time);
                    $this->redirect('/', 302, true);
                } else {
                    $this->error();
                }
                break;
        }
    }

    public function logout()
    {
        CSRFToken::destroy();
        Session::remove('FORMWORK_USERNAME');
        Session::destroy();
        $this->notify($this->label('login.logged-out'), 'success');
        $this->redirect('/', 302, true);
    }

    protected function error()
    {
        $this->notify($this->label('login.attempt.failed'), 'error');
        $this->view('authentication.login', array('error' => true, 'csrfToken' => CSRFToken::get()));
    }
}
