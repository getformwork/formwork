<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Security\Password;
use Formwork\Admin\Uploader;
use Formwork\Admin\Users\User;
use Formwork\Data\DataGetter;
use Formwork\Data\DataSetter;
use Formwork\Exceptions\TranslatedException;
use Formwork\Fields\Fields;
use Formwork\Files\Image;
use Formwork\Formwork;
use Formwork\Parsers\YAML;
use Formwork\Response\RedirectResponse;
use Formwork\Response\Response;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Registry;

class UsersController extends AbstractController
{
    /**
     * Users@index action
     */
    public function index(): Response
    {
        $this->ensurePermission('users.index');

        $this->modal('newUser');

        $this->modal('deleteUser');

        return new Response($this->view('users.index', [
            'title'   => $this->admin()->translate('admin.users.users'),
            'users'   => $this->admin()->users()
        ], true));
    }

    /**
     * Users@create action
     */
    public function create(): RedirectResponse
    {
        $this->ensurePermission('users.create');

        $data = HTTPRequest::postData();

        // Ensure no required data is missing
        if (!$data->hasMultiple(['username', 'fullname', 'password', 'email', 'language'])) {
            $this->admin()->notify($this->admin()->translate('admin.users.user.cannot-create.var-missing'), 'error');
            return $this->admin()->redirect('/users/');
        }

        // Ensure there isn't a user with the same username
        if ($this->admin()->users()->has($data->get('username'))) {
            $this->admin()->notify($this->admin()->translate('admin.users.user.cannot-create.already-exists'), 'error');
            return $this->admin()->redirect('/users/');
        }

        $userData = [
            'username' => $data->get('username'),
            'fullname' => $data->get('fullname'),
            'hash'     => Password::hash($data->get('password')),
            'email'    => $data->get('email'),
            'language' => $data->get('language')
        ];

        YAML::encodeToFile($userData, Formwork::instance()->config()->get('admin.paths.accounts') . $data->get('username') . '.yml');

        $this->admin()->notify($this->admin()->translate('admin.users.user.created'), 'success');
        return $this->admin()->redirect('/users/');
    }

    /**
     * Users@delete action
     */
    public function delete(RouteParams $params): RedirectResponse
    {
        $this->ensurePermission('users.delete');

        $user = $this->admin()->users()->get($params->get('user'));

        try {
            if (!$user) {
                throw new TranslatedException(sprintf('User "%s" not found', $params->get('user')), 'admin.users.user.not-found');
            }
            if (!$this->user()->canDeleteUser($user)) {
                throw new TranslatedException(
                    sprintf('Cannot delete user "%s", you must be an administrator and the user must not be logged in', $user->username()),
                    'users.user.cannot-delete'
                );
            }
            FileSystem::delete(Formwork::instance()->config()->get('admin.paths.accounts') . $user->username() . '.yml');
            $this->deleteAvatar($user);
        } catch (TranslatedException $e) {
            $this->admin()->notify($e->getTranslatedMessage(), 'error');
            return $this->admin()->redirectToReferer(302, '/users/');
        }

        $lastAccessRegistry = new Registry(Formwork::instance()->config()->get('admin.paths.logs') . 'lastAccess.json');

        // Remove user last access from registry
        $lastAccessRegistry->remove($user->username());

        $this->admin()->notify($this->admin()->translate('admin.users.user.deleted'), 'success');
        return $this->admin()->redirect('/users/');
    }

    /**
     * Users@profile action
     */
    public function profile(RouteParams $params): Response
    {
        $fields = new Fields(Formwork::instance()->schemes()->get('admin', 'user')->get('fields'));

        $user = $this->admin()->users()->get($params->get('user'));

        if ($user === null) {
            $this->admin()->notify($this->admin()->translate('admin.users.user.not-found'), 'error');
            return $this->admin()->redirect('/users/');
        }

        // Disable password and/or role fields if they cannot be changed
        $fields->find('password')->set('disabled', !$this->user()->canChangePasswordOf($user));
        $fields->find('role')->set('disabled', !$this->user()->canChangeRoleOf($user));

        if (HTTPRequest::method() === 'POST') {
            // Ensure that options can be changed
            if ($this->user()->canChangeOptionsOf($user)) {
                $data = DataSetter::fromGetter(HTTPRequest::postData());
                $fields->validate($data);
                try {
                    $this->updateUser($user, $data);
                    $this->admin()->notify($this->admin()->translate('admin.users.user.edited'), 'success');
                } catch (TranslatedException $e) {
                    $this->admin()->notify($this->admin()->translate($e->getLanguageString(), $user->username()), 'error');
                }
            } else {
                $this->admin()->notify($this->admin()->translate('admin.users.user.cannot-edit', $user->username()), 'error');
            }

            return $this->admin()->redirect('/users/' . $user->username() . '/profile/');
        }

        $fields->validate(new DataGetter($user->toArray()));

        $this->modal('changes');

        $this->modal('deleteUser');

        return new Response($this->view('users.profile', [
            'title'   => $this->admin()->translate('admin.users.user-profile', $user->username()),
            'user'    => $user,
            'fields'  => $fields->render(true)
        ], true));
    }

    /**
     * Update user data from POST request
     */
    protected function updateUser(User $user, DataSetter $data): void
    {
        // Remove CSRF token from $data
        $data->remove('csrf-token');

        if (!empty($data->get('password'))) {
            // Ensure that password can be changed
            if (!$this->user()->canChangePasswordOf($user)) {
                throw new TranslatedException(sprintf('Cannot change the password of %s', $user->username()), 'admin.users.user.cannot-change-password');
            }

            // Hash the new password
            $data->set('hash', Password::hash($data->get('password')));
        }

        // Remove password from $data
        $data->remove('password');

        // Ensure that user role can be changed
        if ($data->get('role', $user->role()) !== $user->role() && !$this->user()->canChangeRoleOf($user)) {
            throw new TranslatedException(sprintf('Cannot change the role of %s', $user->username()), 'admin.users.user.cannot-change-role');
        }

        // Handle incoming files
        if (HTTPRequest::hasFiles() && ($avatar = $this->uploadAvatar($user)) !== null) {
            $data->set('avatar', $avatar);
        }

        // Filter empty elements from $data and merge them with $user ones
        $userData = array_merge($user->toArray(), $data->toArray());

        YAML::encodeToFile($userData, Formwork::instance()->config()->get('admin.paths.accounts') . $user->username() . '.yml');
    }

    /**
     * Upload a new avatar for a user
     */
    protected function uploadAvatar(User $user): ?string
    {
        $avatarsPath = ADMIN_PATH . 'avatars' . DS;

        $uploader = new Uploader(
            $avatarsPath,
            [
                'allowedMimeTypes' => ['image/gif', 'image/jpeg', 'image/png', 'image/webp']
            ]
        );

        $hasUploaded = $uploader->upload(FileSystem::randomName());

        if ($hasUploaded) {
            $avatarSize = Formwork::instance()->config()->get('admin.avatar_size');

            // Square off uploaded avatar
            $image = new Image($avatarsPath . $uploader->uploadedFiles()[0]);
            $image->square($avatarSize)->save();

            // Delete old avatar
            $this->deleteAvatar($user);

            $this->admin()->notify($this->admin()->translate('admin.user.avatar.uploaded'), 'success');
            return $uploader->uploadedFiles()[0];
        }
    }

    /**
     * Delete the avatar of a given user
     */
    protected function deleteAvatar(User $user): void
    {
        $avatar = $user->avatar()->path();
        if ($avatar !== null && FileSystem::exists($avatar)) {
            FileSystem::delete($avatar);
        }
    }
}
