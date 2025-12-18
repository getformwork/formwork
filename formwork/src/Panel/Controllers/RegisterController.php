<?php

namespace Formwork\Panel\Controllers;

use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Log\Log;
use Formwork\Log\Registry;
use Formwork\Panel\Security\Password;
use Formwork\Parsers\Yaml;
use Formwork\Schemes\Schemes;
use Formwork\Users\User;
use Formwork\Utils\FileSystem;
use RuntimeException;

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

        switch ($this->request->method()) {
            case RequestMethod::GET:
                return new Response($this->view('register.register', [
                    'title'  => $this->translate('panel.register.register'),
                    'fields' => $fields,
                ]));

            case RequestMethod::POST:
                try {
                    $fields->setValues($this->request->input())->validate();
                } catch (ValidationException) {
                    $this->panel->notify($this->translate('panel.users.user.cannotCreate.varMissing'), 'error');
                    return $this->redirect($this->generateRoute('panel.index'));
                }

                $username = $fields->get('username')->value();

                $userData = [
                    'username' => $username,
                    'fullname' => $fields->get('fullname')->value(),
                    'hash'     => Password::hash($fields->get('password')->value()),
                    'email'    => $fields->get('email')->value(),
                    'language' => $fields->get('language')->value(),
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

        throw new RuntimeException('Invalid Method');
    }
}
