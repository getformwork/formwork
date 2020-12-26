<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\Security\CSRFToken;
use Formwork\Admin\Security\Password;
use Formwork\Parsers\YAML;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Log;
use Formwork\Utils\Registry;
use Formwork\Utils\Session;

class RegisterController extends AbstractController
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
                    'title' => $this->admin()->translate('admin.register.register')
                ]);

                break;

            case 'POST':
                $data = HTTPRequest::postData();

                if (!$data->hasMultiple(['username', 'fullname', 'password', 'language', 'email'])) {
                    $this->admin()->notify($this->admin()->translate('admin.users.user.cannot-create.var-missing'), 'error');
                    $this->admin()->redirectToPanel();
                }

                $userData = [
                    'username' => $data->get('username'),
                    'fullname' => $data->get('fullname'),
                    'hash'     => Password::hash($data->get('password')),
                    'email'    => $data->get('email'),
                    'language' => $data->get('language'),
                    'role'     => 'admin'
                ];

                YAML::encodeToFile($userData, Admin::ACCOUNTS_PATH . $data->get('username') . '.yml');

                Session::set('FORMWORK_USERNAME', $data->get('username'));

                $accessLog = new Log(Admin::LOGS_PATH . 'access.json');
                $lastAccessRegistry = new Registry(Admin::LOGS_PATH . 'lastAccess.json');

                $time = $accessLog->log($data->get('username'));
                $lastAccessRegistry->set($data->get('username'), $time);

                $this->admin()->redirectToPanel();

                break;
        }
    }
}
