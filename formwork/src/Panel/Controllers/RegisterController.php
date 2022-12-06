<?php

namespace Formwork\Panel\Controllers;

use Formwork\Formwork;
use Formwork\Panel\Security\CSRFToken;
use Formwork\Panel\Security\Password;
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
        if (!$this->panel()->users()->isEmpty()) {
            return $this->redirectToReferer();
        }

        Session::regenerate(false);
        CSRFToken::generate();

        switch (HTTPRequest::method()) {
            case 'GET':
                return new Response($this->view('register.register', [
                    'title' => $this->translate('panel.register.register')
                ], true));

                break;

            case 'POST':
                $data = HTTPRequest::postData();

                if (!$data->hasMultiple(['username', 'fullname', 'password', 'language', 'email'])) {
                    $this->panel()->notify($this->translate('panel.users.user.cannotCreate.varMissing'), 'error');
                    return $this->redirectToPanel();
                }

                $userData = [
                    'username' => $data->get('username'),
                    'fullname' => $data->get('fullname'),
                    'hash'     => Password::hash($data->get('password')),
                    'email'    => $data->get('email'),
                    'language' => $data->get('language'),
                    'role'     => 'panel'
                ];

                YAML::encodeToFile($userData, Formwork::instance()->config()->get('panel.paths.accounts') . $data->get('username') . '.yml');

                Session::set('FORMWORK_USERNAME', $data->get('username'));

                $accessLog = new Log(Formwork::instance()->config()->get('panel.paths.logs') . 'access.json');
                $lastAccessRegistry = new Registry(Formwork::instance()->config()->get('panel.paths.logs') . 'lastAccess.json');

                $time = $accessLog->log($data->get('username'));
                $lastAccessRegistry->set($data->get('username'), $time);

                return $this->redirectToPanel();

                break;
        }
    }
}
