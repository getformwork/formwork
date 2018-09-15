<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Security\CSRFToken;
use Formwork\Admin\Security\Password;
use Formwork\Admin\Utils\Session;
use Formwork\Data\DataGetter;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;

class Register extends AbstractController
{
    public function register()
    {
        CSRFToken::generate();

        switch (HTTPRequest::method()) {
            case 'GET':
                $this->view('register.register', array(
                    'title' => $this->label('register.register')
                ));
                break;

            case 'POST':
                $this->data = new DataGetter(HTTPRequest::postData());

                foreach (array('username', 'fullname', 'password', 'email') as $var) {
                    if (!$this->data->has($var)) {
                        $this->notify($this->label('users.user.cannot-create.var-missing', $var), 'error');
                        $this->redirectToPanel(302, true);
                    }
                }

                $userdata = array(
                    'username' => $this->data->get('username'),
                    'fullname' => $this->data->get('fullname'),
                    'hash'     => Password::hash($this->data->get('password')),
                    'email'    => $this->data->get('email'),
                    'avatar'   => null,
                    'language' => $this->data->get('language')
                );

                $fileContent = YAML::encode($userdata);

                FileSystem::write(ACCOUNTS_PATH . $this->data->get('username') . '.yml', $fileContent);

                Session::set('FORMWORK_USERNAME', $this->data->get('username'));
                $time = $this->log('access')->log($this->data->get('username'));
                $this->registry('lastAccess')->set($this->data->get('username'), $time);

                $this->redirectToPanel(302, true);
                break;
        }
    }
}
