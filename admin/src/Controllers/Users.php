<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\Exceptions\TranslatedException;
use Formwork\Admin\Fields\Fields;
use Formwork\Admin\Security\Password;
use Formwork\Admin\Uploader;
use Formwork\Admin\Users\User;
use Formwork\Data\DataGetter;
use Formwork\Data\DataSetter;
use Formwork\Files\Image;
use Formwork\Parsers\YAML;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;

class Users extends AbstractController
{
    /**
     * Users@index action
     */
    public function index()
    {
        $this->ensurePermission('users.index');

        $this->modal('newUser');

        $this->modal('deleteUser');

        $this->view('admin', [
            'title'   => $this->label('users.users'),
            'content' => $this->view('users.index', [
                'users' => Admin::instance()->users()
            ], true)
        ]);
    }

    /**
     * Users@create action
     */
    public function create()
    {
        $this->ensurePermission('users.create');

        $data = new DataGetter(HTTPRequest::postData());

        // Ensure no required data is missing
        if (!$data->has(['username', 'fullname', 'password', 'email', 'language'])) {
            $this->notify($this->label('users.user.cannot-create.var-missing'), 'error');
            $this->redirect('/users/');
        }

        // Ensure there isn't a user with the same username
        if (Admin::instance()->users()->has($data->get('username'))) {
            $this->notify($this->label('users.user.cannot-create.already-exists'), 'error');
            $this->redirect('/users/');
        }

        $userData = [
            'username' => $data->get('username'),
            'fullname' => $data->get('fullname'),
            'hash'     => Password::hash($data->get('password')),
            'email'    => $data->get('email'),
            'language' => $data->get('language')
        ];

        FileSystem::write(Admin::ACCOUNTS_PATH . $data->get('username') . '.yml', YAML::encode($userData));

        $this->notify($this->label('users.user.created'), 'success');
        $this->redirect('/users/');
    }

    /**
     * Users@delete action
     *
     * @param RouteParams $params
     */
    public function delete(RouteParams $params)
    {
        $this->ensurePermission('users.delete');

        $user = Admin::instance()->users()->get($params->get('user'));

        try {
            if (!$user) {
                throw new TranslatedException('User ' . $params->get('user') . ' not found', 'users.user.not-found');
            }
            if (!$this->user()->canDeleteUser($user)) {
                throw new TranslatedException(
                    'Cannot delete user ' . $user->username() . ', you must be an administrator and the user must not be logged in',
                    'users.user.cannot-delete'
                );
            }
            FileSystem::delete(Admin::ACCOUNTS_PATH . $user->username() . '.yml');
            $this->deleteAvatar($user);
        } catch (TranslatedException $e) {
            $this->notify($e->getTranslatedMessage(), 'error');
            $this->redirectToReferer(302, '/users/');
        }

        // Remove user last access from registry
        $this->registry('lastAccess')->remove($user->username());

        $this->notify($this->label('users.user.deleted'), 'success');
        $this->redirect('/users/');
    }

    /**
     * Users@profile action
     *
     * @param RouteParams $params
     */
    public function profile(RouteParams $params)
    {
        $fields = new Fields(YAML::parseFile(Admin::SCHEMES_PATH . 'user.yml'));

        $user = Admin::instance()->users()->get($params->get('user'));

        if ($user === null) {
            $this->notify($this->label('users.user.not-found'), 'error');
            $this->redirect('/users/');
        }

        $fields->validate($user);

        // Disable password and/or role fields if they cannot be changed
        $fields->find('password')->set('disabled', !$this->user()->canChangePasswordOf($user));
        $fields->find('role')->set('disabled', !$this->user()->canChangeRoleOf($user));

        if (HTTPRequest::method() === 'POST') {
            // Ensure that options can be changed
            if ($this->user()->canChangeOptionsOf($user)) {
                $this->updateUser($user);
                $this->notify($this->label('users.user.edited'), 'success');
            } else {
                $this->notify($this->label('users.user.cannot-edit', $user->username()), 'error');
            }
            $this->redirect('/users/' . $user->username() . '/profile/');
        }

        $this->modal('changes');

        $this->modal('deleteUser');

        $this->view('admin', [
            'title'   => $this->label('users.user-profile', $user->username()),
            'content' => $this->view('users.profile', [
                'user'   => $user,
                'fields' => $fields->render(true)
            ], true)
        ]);
    }

    /**
     * Update user data from POST request
     *
     * @param User $user
     */
    protected function updateUser(User $user)
    {
        $data = new DataSetter(HTTPRequest::postData());

        // Remove CSRF token from $data
        $data->set('csrf-token', null);

        if (!empty($data->get('password'))) {
            // Ensure that password can be changed
            if (!$this->user()->canChangePasswordOf($user)) {
                $this->notify($this->label('users.user.cannot-change-password'), 'error');
                $this->redirect('/users/' . $user->username() . '/profile/');
            }

            // Hash the new password
            $data->set('hash', Password::hash($data->get('password')));

            // Remove password from $data
            $data->set('password', null);
        }

        if ($data->get('role', $user->role()) !== $user->role()) {
            // Ensure that user role can be changed
            if (!$this->user()->canChangeRoleOf($user)) {
                $this->notify($this->label('users.user.cannot-change-role', $user->username()), 'error');
                $this->redirect('/users/' . $user->username() . '/profile/');
            }
        }

        // Handle incoming files
        if (HTTPRequest::hasFiles()) {
            if (($avatar = $this->uploadAvatar($user)) !== null) {
                $data->set('avatar', $avatar);
            }
        }

        // Filter empty elements from $data and merge them with $user ones
        $userData = array_merge($user->toArray(), array_filter($data->toArray()));

        FileSystem::write(Admin::ACCOUNTS_PATH . $user->username() . '.yml', YAML::encode($userData));
    }

    /**
     * Upload a new avatar for a user
     *
     * @param User $user
     */
    protected function uploadAvatar(User $user)
    {
        $avatarsPath = ADMIN_PATH . 'avatars' . DS;

        $uploader = new Uploader(
            $avatarsPath,
            [
                'allowedMimeTypes' => ['image/gif', 'image/jpeg', 'image/png']
            ]
        );

        try {
            $hasUploaded = $uploader->upload(FileSystem::randomName());
        } catch (TranslatedException $e) {
            $this->notify($this->label('uploader.error', $e->getTranslatedMessage()), 'error');
            $this->redirect('/users/' . $user->username() . '/profile/');
        }

        if ($hasUploaded) {
            $avatarSize = $this->option('admin.avatar_size');

            // Square off uploaded avatar
            $image = new Image($avatarsPath . $uploader->uploadedFiles()[0]);
            $image->square($avatarSize)->save();

            // Delete old avatar
            $this->deleteAvatar($user);

            $this->notify($this->label('user.avatar.uploaded'), 'success');
            return $uploader->uploadedFiles()[0];
        }
    }

    /**
     * Delete the avatar of a given user
     *
     * @param User $user
     */
    protected function deleteAvatar(User $user)
    {
        $avatar = $user->avatar()->path();
        if (FileSystem::exists($avatar)) {
            FileSystem::delete($avatar);
        }
    }
}
