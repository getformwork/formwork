<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\Exceptions\LocalizedException;
use Formwork\Admin\Fields\Fields;
use Formwork\Admin\Image;
use Formwork\Admin\Security\Password;
use Formwork\Admin\Uploader;
use Formwork\Admin\Users\User;
use Formwork\Core\Formwork;
use Formwork\Data\DataGetter;
use Formwork\Parsers\YAML;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;

class Users extends AbstractController
{
    public function index()
    {
        $this->ensurePermission('users.index');

        $this->modal('newUser');

        $this->modal('deleteUser');

        $this->view('admin', array(
            'title' => $this->label('users.users'),
            'content' => $this->view('users.index', array(
                'users' => Admin::instance()->users()
            ), false)
        ));
    }

    public function create()
    {
        $this->ensurePermission('users.create');

        $data = new DataGetter(HTTPRequest::postData());

        // Ensure no required data is missing
        foreach (array('username', 'fullname', 'password', 'email', 'language') as $var) {
            if (!$data->has($var)) {
                $this->notify($this->label('users.user.cannot-create.var-missing', $var), 'error');
                $this->redirect('/users/', 302, true);
            }
        }

        // Ensure there isn't a user with the same username
        if (Admin::instance()->users()->has($data->get('username'))) {
            $this->notify($this->label('users.user.cannot-create.already-exists'), 'error');
            $this->redirect('/users/', 302, true);
        }

        $userData = array(
            'username' => $data->get('username'),
            'fullname' => $data->get('fullname'),
            'hash'     => Password::hash($data->get('password')),
            'email'    => $data->get('email'),
            'language' => $data->get('language'),
            'avatar'   => null,
            'role'     => 'user'
        );

        FileSystem::write(ACCOUNTS_PATH . $data->get('username') . '.yml', YAML::encode($userData));

        $this->notify($this->label('users.user.created'), 'success');
        $this->redirect('/users/', 302, true);
    }

    public function delete(RouteParams $params)
    {
        $this->ensurePermission('users.delete');

        try {
            $user = Admin::instance()->users()->get($params->get('user'));
            if (!$user) {
                throw new LocalizedException('User ' . $params->get('user') . ' not found', 'users.user.not-found');
            }
            if (!$this->user()->canDeleteUser($user)) {
                throw new LocalizedException('Cannot delete user, you must be an administrator and the user must not be logged in', 'users.user.cannot-delete');
            }
            $this->deleteAvatar($user);
            FileSystem::delete(ACCOUNTS_PATH . $params->get('user') . '.yml');
            $this->registry('lastAccess')->remove($params->get('user'));
            $this->notify($this->label('users.user.deleted'), 'success');
            $this->redirect('/users/', 302, true);
        } catch (LocalizedException $e) {
            $this->notify($e->getLocalizedMessage(), 'error');
            $this->redirect('/users/', 302, true);
        }
    }

    public function profile(RouteParams $params)
    {
        $fields = new Fields(YAML::parseFile(SCHEMES_PATH . 'user.yml'));

        $user = Admin::instance()->users()->get($params->get('user'));

        $fields->validate($user);

        $fields->find('password')->set('disabled', !$this->user()->canChangePasswordOf($user));
        $fields->find('role')->set('disabled', !$this->user()->canChangeRoleOf($user));

        if (is_null($user)) {
            $this->notify($this->label('users.user.not-found'), 'error');
            $this->redirect('/users/', 302, true);
        }

        if (HTTPRequest::method() === 'POST') {
            if ($this->user()->canChangeOptionsOf($user)) {
                $this->updateUser($user);
                $this->notify($this->label('users.user.edited'), 'success');
            } else {
                $this->notify($this->label('users.user.cannot-edit', $user->username()), 'error');
            }
            $this->redirect('/users/' . $user->username() . '/profile/', 302, true);
        }

        $this->modal('changes');

        $this->modal('deleteUser');

        $this->view('admin', array(
            'title' => $this->label('users.user-profile', $user->username()),
            'content' => $this->view('users.profile', array(
                'user' => $user,
                'fields' => $this->fields($fields, false)
            ), false)
        ));
    }

    protected function updateUser(User $user)
    {
        $data = $user->toArray();

        $postData = HTTPRequest::postData();

        unset($postData['csrf-token']);

        if (!empty($postData['password'])) {
            if (!$this->user()->canChangePasswordOf($user)) {
                $this->notify($this->label('users.user.cannot-change-password'), 'error');
                $this->redirect('/users/' . $user->username() . '/profile/', 302, true);
            }
            $postData['hash'] = Password::hash($postData['password']);
            unset($postData['password']);
        }

        if (!empty($postData['role']) && $postData['role'] !== $user->role() && !$this->user()->canChangeRoleOf($user)) {
            $this->notify($this->label('users.user.cannot-change-role', $user->username()), 'error');
            $this->redirect('/users/' . $user->username() . '/profile/', 302, true);
        }

        foreach ($postData as $key => $value) {
            if (!empty($value)) {
                $data[$key] = $value;
            }
        }

        if (HTTPRequest::hasFiles()) {
            if (!is_null($avatar = $this->uploadAvatar($user))) {
                $data['avatar'] = $avatar;
            }
        }

        FileSystem::write(ACCOUNTS_PATH . $data['username'] . '.yml', YAML::encode($data));
    }

    protected function uploadAvatar(User $user)
    {
        $avatarsPath = ADMIN_PATH . 'avatars' . DS;

        $uploader = new Uploader(
            $avatarsPath,
            array(
                'allowedMimeTypes' => array('image/gif', 'image/jpeg', 'image/png')
            )
        );

        try {
            if ($uploader->upload(FileSystem::randomName())) {
                $avatarSize = Formwork::instance()->option('admin.avatar_size');
                $image = new Image($avatarsPath . $uploader->uploadedFiles()[0]);
                $image->square($avatarSize)->save();
                $this->deleteAvatar($user);
                $this->notify($this->label('user.avatar.uploaded'), 'success');
                return $uploader->uploadedFiles()[0];
            }
        } catch (LocalizedException $e) {
            $this->notify($this->label('uploader.error', $e->getLocalizedMessage()), 'error');
            $this->redirect('/users/' . $user->username() . '/profile/', 302, true);
        }
    }

    protected function deleteAvatar(User $user)
    {
        $avatar = $user->avatar()->path();
        if (FileSystem::exists($avatar)) {
            FileSystem::delete($avatar);
        }
    }
}
