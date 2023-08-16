<?php

namespace Formwork\Panel\Controllers;

use Formwork\Http\Request;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Log\Log;
use Formwork\Log\Registry;
use Formwork\Panel\Security\Password;
use Formwork\Parsers\Yaml;
use Formwork\Security\CsrfToken;
use Formwork\Utils\FileSystem;

class RegisterController extends AbstractController
{
    /**
     * Register@register action
     */
    public function register(Request $request, CsrfToken $csrfToken): Response
    {
        if (!$this->panel()->users()->isEmpty()) {
            return $this->redirectToReferer();
        }

        $request->session()->regenerate(false);
        $csrfToken->generate();

        switch ($request->method()) {
            case RequestMethod::GET:
                return new Response($this->view('register.register', [
                    'title' => $this->translate('panel.register.register'),
                ], return: true));

                break;

            case RequestMethod::POST:
                $data = $request->input();

                if (!$data->hasMultiple(['username', 'fullname', 'password', 'language', 'email'])) {
                    $this->panel()->notify($this->translate('panel.users.user.cannotCreate.varMissing'), 'error');
                    return $this->redirect($this->generateRoute('panel.index'));
                }

                $userData = [
                    'username' => $data->get('username'),
                    'fullname' => $data->get('fullname'),
                    'hash'     => Password::hash($data->get('password')),
                    'email'    => $data->get('email'),
                    'language' => $data->get('language'),
                    'role'     => 'admin',
                ];

                Yaml::encodeToFile($userData, FileSystem::joinPaths($this->config->get('system.panel.paths.accounts'), $data->get('username') . '.yaml'));

                $request->session()->set('FORMWORK_USERNAME', $data->get('username'));

                $accessLog = new Log(FileSystem::joinPaths($this->config->get('system.panel.paths.logs'), 'access.json'));
                $lastAccessRegistry = new Registry(FileSystem::joinPaths($this->config->get('system.panel.paths.logs'), 'lastAccess.json'));

                $time = $accessLog->log($data->get('username'));
                $lastAccessRegistry->set($data->get('username'), $time);

                return $this->redirect($this->generateRoute('panel.index'));

                break;
        }
    }
}
