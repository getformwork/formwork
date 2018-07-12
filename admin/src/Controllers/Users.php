<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\Security\CSRFToken;
use Formwork\Admin\Security\Password;
use Formwork\Admin\Uploader;
use Formwork\Admin\Utils\Registry;
use Formwork\Data\DataGetter;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPRequest;
use Exception;
use Spyc;

class Users extends AbstractController
{
    public function run(RouteParams $params)
    {
        Admin::instance()->ensureLogin();
        $content = $this->view(
            'users.index',
            array(
                'users' => Admin::instance()->users()
            ),
            false
        );

        $modals[] = $this->view(
            'modals.newUser',
            array('csrfToken' => CSRFToken::get()),
            false
        );

        $modals[] = $this->view(
            'modals.deleteUser',
            array('csrfToken' => CSRFToken::get()),
            false
        );

        $this->view('admin', array(
            'location' => 'users',
            'content' => $content,
            'modals' => implode($modals)
        ));
    }

    public function new(RouteParams $params)
    {
        $this->data = new DataGetter(HTTPRequest::postData());

        // Ensure no required data is missing
        foreach (array('username', 'fullname', 'password', 'email', 'language') as $var) {
            if (!$this->data->has($var)) {
                $this->notify($this->label('users.user.cannot-create.var-missing', $var), 'error');
                $this->redirect('/users/', 302, true);
            }
        }

        // Ensure there isn't a user with the same username
        if (Admin::instance()->users()->has($this->data->get('username'))) {
            $this->notify($this->label('users.user.cannot-create.already-exists'), 'error');
            $this->redirect('/users/', 302, true);
        }

        $userdata = array(
            'username' => $this->data->get('username'),
            'fullname' => $this->data->get('fullname'),
            'hash'     => Password::hash($this->data->get('password')),
            'email'    => $this->data->get('email'),
            'avatar'   => null,
            'language' => $this->data->get('language')
        );

        $fileContent = Spyc::YAMLdump($userdata, false, 0, true);

        FileSystem::write(ACCOUNTS_PATH . $this->data->get('username') . '.yml', $fileContent);

        $this->notify($this->label('users.user.created'), 'success');
        $this->redirect('/users/', 302, true);
    }

    public function delete(RouteParams $params)
    {
        try {
            $user = Admin::instance()->users()->get($params->get('user'));
            if (!$user) {
                throw new Exception($this->label('users.user.not-found'));
            }
            if ($user->logged()) {
                throw new Exception($this->label('users.user.cannot-delete.logged'));
            }
            $this->deleteAvatar($user);
            FileSystem::delete(ACCOUNTS_PATH . $params->get('user') . '.yml');
            $this->registry('lastAccess')->remove($params->get('user'));
            $this->notify($this->label('users.user.deleted'), 'success');
            $this->redirect('/users/', 302, true);
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
            $this->redirect('/users/', 302, true);
        }
    }

    public function profile(RouteParams $params)
    {
        Admin::instance()->ensureLogin();
        $user = Admin::instance()->users()->get($params->get('user'));

        if (is_null($user)) {
            $this->notify($this->label('users.user.not-found'), 'error');
            $this->redirect('/users/', 302, true);
        }

        if (HTTPRequest::method() == 'POST') {
            $data = $user->toArray();

            $postData = HTTPRequest::postData();

            unset($postData['csrf-token']);

            if (!empty($postData['password'])) {
                $postData['hash'] = Password::hash($postData['password']);
                unset($postData['password']);
            }

            foreach ($postData as $key => $value) {
                if (!empty($value)) {
                    $data[$key] = $value;
                }
            }

            if (HTTPRequest::hasFiles()) {
                $uploader = new Uploader(
                    ADMIN_PATH . 'avatars' . DS,
                    array('allowedMimeTypes' => array('image/gif', 'image/jpeg', 'image/png'))
                );
                try {
                    if ($uploader->upload(str_shuffle(uniqid()))) {
                        $this->deleteAvatar($user);
                        $data['avatar'] = $uploader->uploadedFiles()[0];
                        $this->notify($this->label('user.avatar.uploaded'), 'success');
                    }
                } catch (Exception $e) {
                    $this->notify($this->label('uploader.error', $e->getMessage()), 'error');
                    $this->redirect('/users/' . $user->username() . '/profile/', 302, true);
                }
            }

            $fileContent = Spyc::YAMLdump($data, false, 0, true);

            FileSystem::write(ACCOUNTS_PATH . $data['username'] . '.yml', $fileContent);

            $this->notify($this->label('users.user.edited'), 'success');
            $this->redirect('/users/' . $user->username() . '/profile/', 302, true);
        }

        $content = $this->view(
            'users.profile',
            array(
                'user' => $user,
                'csrfToken' => CSRFToken::get()
            ),
            false
        );

        $modals = $this->view(
            'modals.changes',
            array(),
            false
        );

        $this->view('admin', array(
            'location' => 'users',
            'content' => $content,
            'modals' => $modals
        ));
    }

    protected function deleteAvatar(User $user)
    {
        $avatar = $user->avatar()->path();
        if (FileSystem::exists($avatar)) {
            FileSystem::delete($avatar);
        }
    }
}
