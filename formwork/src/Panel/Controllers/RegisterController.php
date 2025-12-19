<?php

namespace Formwork\Panel\Controllers;

use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Log\Log;
use Formwork\Log\Registry;
use Formwork\Panel\Security\Password;
use Formwork\Parsers\Yaml;
use Formwork\Schemes\Schemes;
use Formwork\Users\User;
use Formwork\Utils\FileSystem;

final class RegisterController extends AbstractController
{
    /**
     * Register@register action
     */
    public function register(Schemes $schemes): Response
    {
        if (!$this->site->users()->isEmpty()) {
            return $this->redirectToReferer(default: $this->generateRoute('panel.index'), base: $this->panel->panelRoot());
        }

        $this->csrfToken->generate($this->panel->getCsrfTokenName());

        $fields = $schemes->get('forms.register')->fields();

        if ($this->request->method() === RequestMethod::GET) {
            return new Response($this->view('@panel.register.register', [
                'title'  => $this->translate('panel.register.register'),
                'fields' => $fields,
            ]));
        }

        $form = $this->form('register', $fields)
            ->processRequest($this->request, uploadFiles: false);

        if (!$form->isValid()) {
            return new Response($this->view('@panel.register.register', [
                'title'  => $this->translate('panel.register.register'),
                'fields' => $form->fields(),
            ]), $form->getResponseStatus());
        }

        $data = $form->data();
        $username = $data->get('username');

        $userData = [
            'username' => $username,
            'fullname' => $data->get('fullname'),
            'hash'     => Password::hash($data->get('password')),
            'email'    => $data->get('email'),
            'language' => $data->get('language'),
            'role'     => 'admin',
        ];

        Yaml::encodeToFile($userData, FileSystem::joinPaths($this->config->get('system.users.paths.accounts'), $username . '.yaml'));

        $this->request->session()->regenerate();
        $this->request->session()->set(User::SESSION_LOGGED_USER_KEY, $username);

        $accessLog = new Log(FileSystem::joinPaths($this->config->get('system.panel.paths.logs'), 'access.json'));
        $lastAccessRegistry = new Registry(FileSystem::joinPaths($this->config->get('system.panel.paths.logs'), 'lastAccess.json'));

        $time = $accessLog->log($username);
        $lastAccessRegistry->set($username, $time);

        return $this->redirect($this->generateRoute('panel.index'));
    }
}
