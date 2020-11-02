<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\Security\CSRFToken;
use Formwork\Admin\Security\Password;
use Formwork\Admin\Utils\Session;
use Formwork\Data\DataGetter;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;

class Register extends AbstractController
{
    /**
     * Register@register action
     */
    public function register(): void
    {
        CSRFToken::generate();

        switch (HTTPRequest::method()) {
            case 'GET':
                $this->view('register.register', [
                    'title' => $this->label('register.register')
                ]);

                break;

            case 'POST':
                $data = new DataGetter(HTTPRequest::postData());

                if (!$data->hasMultiple(['username', 'fullname', 'password', 'language', 'email'])) {
                    $this->notify($this->label('users.user.cannot-create.var-missing'), 'error');
                    $this->redirectToPanel();
                }

                $userData = [
                    'username' => $data->get('username'),
                    'fullname' => $data->get('fullname'),
                    'hash'     => Password::hash($data->get('password')),
                    'email'    => $data->get('email'),
                    'language' => $data->get('language'),
                    'role'     => 'admin'
                ];

                FileSystem::write(Admin::ACCOUNTS_PATH . $data->get('username') . '.yml', YAML::encode($userData));

                Session::set('FORMWORK_USERNAME', $data->get('username'));
                $time = $this->log('access')->log($data->get('username'));
                $this->registry('lastAccess')->set($data->get('username'), $time);

                $this->redirectToPanel();

                break;
        }
    }
}
