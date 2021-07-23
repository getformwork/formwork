<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Security\CSRFToken;
use Formwork\Admin\Security\Password;
use Formwork\Formwork;
use Formwork\Parsers\YAML;
use Formwork\Response\Response;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Log;
use Formwork\Utils\Registry;
use Formwork\Utils\Session;

class RegisterController extends AbstractController
{
    /**
     * Register@register action
     */
    public function register(): Response
    {
        Session::regenerate(false);
        CSRFToken::generate();

        switch (HTTPRequest::method()) {
            case 'GET':
                return new Response($this->view('register.register', [
                    'title' => $this->admin()->translate('admin.register.register')
                ], true));

                break;

            case 'POST':
                $data = HTTPRequest::postData();

                if (!$data->hasMultiple(['username', 'fullname', 'password', 'language', 'email'])) {
                    $this->admin()->notify($this->admin()->translate('admin.users.user.cannot-create.var-missing'), 'error');
                    return $this->admin()->redirectToPanel();
                }

                $userData = [
                    'username' => $data->get('username'),
                    'fullname' => $data->get('fullname'),
                    'hash'     => Password::hash($data->get('password')),
                    'email'    => $data->get('email'),
                    'language' => $data->get('language'),
                    'role'     => 'admin'
                ];

                YAML::encodeToFile($userData, Formwork::instance()->config()->get('admin.paths.accounts') . $data->get('username') . '.yml');

                Session::set('FORMWORK_USERNAME', $data->get('username'));

                $accessLog = new Log(Formwork::instance()->config()->get('admin.paths.logs') . 'access.json');
                $lastAccessRegistry = new Registry(Formwork::instance()->config()->get('admin.paths.logs') . 'lastAccess.json');

                $time = $accessLog->log($data->get('username'));
                $lastAccessRegistry->set($data->get('username'), $time);

                return $this->admin()->redirectToPanel();

                break;
        }
    }
}
