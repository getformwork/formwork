<?php

namespace Formwork\Panel\Controllers;

use Formwork\Exceptions\TranslatedException;
use Formwork\Http\Files\UploadedFile;
use Formwork\Http\RedirectResponse;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Images\Image;
use Formwork\Log\Registry;
use Formwork\Panel\Security\Password;
use Formwork\Panel\Users\User;
use Formwork\Parsers\Yaml;
use Formwork\Router\RouteParams;
use Formwork\Uploader;
use Formwork\Utils\FileSystem;

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
            'title' => $this->translate('panel.users.users'),
            'users' => $this->panel()->users(),
        ]));
    }

    /**
     * Users@create action
     */
    public function create(): RedirectResponse
    {
        $this->ensurePermission('users.create');

        $data = $this->request->input();

        // Ensure no required data is missing
        if (!$data->hasMultiple(['username', 'fullname', 'password', 'email', 'language'])) {
            $this->panel()->notify($this->translate('panel.users.user.cannotCreate.varMissing'), 'error');
            return $this->redirect($this->generateRoute('panel.users'));
        }

        // Ensure there isn't a user with the same username
        if ($this->panel()->users()->has($data->get('username'))) {
            $this->panel()->notify($this->translate('panel.users.user.cannotCreate.alreadyExists'), 'error');
            return $this->redirect($this->generateRoute('panel.users'));
        }

        $userData = [
            'username' => $data->get('username'),
            'fullname' => $data->get('fullname'),
            'hash'     => Password::hash($data->get('password')),
            'email'    => $data->get('email'),
            'language' => $data->get('language'),
        ];

        Yaml::encodeToFile($userData, $this->config->get('system.panel.paths.accounts') . $data->get('username') . '.yaml');

        $this->panel()->notify($this->translate('panel.users.user.created'), 'success');
        return $this->redirect($this->generateRoute('panel.users'));
    }

    /**
     * Users@delete action
     */
    public function delete(RouteParams $params): RedirectResponse
    {
        $this->ensurePermission('users.delete');

        $user = $this->panel()->users()->get($params->get('user'));

        try {
            if (!$user) {
                throw new TranslatedException(sprintf('User "%s" not found', $params->get('user')), 'panel.users.user.notFound');
            }
            if (!$this->user()->canDeleteUser($user)) {
                throw new TranslatedException(
                    sprintf('Cannot delete user "%s", you must be an administrator and the user must not be logged in', $user->username()),
                    'users.user.cannotDelete'
                );
            }
            FileSystem::delete($this->config->get('system.panel.paths.accounts') . $user->username() . '.yaml');
            $this->deleteImage($user);
        } catch (TranslatedException $e) {
            $this->panel()->notify($e->getTranslatedMessage(), 'error');
            return $this->redirectToReferer(default: '/users/');
        }

        $lastAccessRegistry = new Registry(FileSystem::joinPaths($this->config->get('system.panel.paths.logs'), 'lastAccess.json'));

        // Remove user last access from registry
        $lastAccessRegistry->remove($user->username());

        $this->panel()->notify($this->translate('panel.users.user.deleted'), 'success');
        return $this->redirect($this->generateRoute('panel.users'));
    }

    /**
     * Users@profile action
     */
    public function profile(RouteParams $params): Response
    {
        $scheme = $this->app->schemes()->get('users.user');

        $fields = $scheme->fields();

        $user = $this->panel()->users()->get($params->get('user'));

        if ($user === null) {
            $this->panel()->notify($this->translate('panel.users.user.notFound'), 'error');
            return $this->redirect($this->generateRoute('panel.users'));
        }

        // Disable password and/or role fields if they cannot be changed
        $fields->get('password')->set('disabled', !$this->user()->canChangePasswordOf($user));
        $fields->get('role')->set('disabled', !$this->user()->canChangeRoleOf($user));

        if ($this->request->method() === RequestMethod::POST) {
            // Ensure that options can be changed
            if ($this->user()->canChangeOptionsOf($user)) {
                $data = $this->request->input()->toArray();

                $fields->setValues($data, null)->validate();

                try {
                    $this->updateUser($user, $data);
                    $this->panel()->notify($this->translate('panel.users.user.edited'), 'success');
                } catch (TranslatedException $e) {
                    $this->panel()->notify($this->translate($e->getLanguageString(), $user->username()), 'error');
                }
            } else {
                $this->panel()->notify($this->translate('panel.users.user.cannotEdit', $user->username()), 'error');
            }

            return $this->redirect($this->generateRoute('panel.users.profile', ['user' => $user->username()]));
        }

        $fields = $fields->setValues($user);

        $this->modal('changes');

        $this->modal('deleteUser');

        return new Response($this->view('users.profile', [
            'title'  => $this->translate('panel.users.userProfile', $user->username()),
            'user'   => $user,
            'fields' => $fields,
        ]));
    }

    /**
     * Update user data from POST request
     *
     * @param array<string, mixed> $data
     */
    protected function updateUser(User $user, array $data): void
    {
        // Remove CSRF token from $data
        unset($data['csrf-token']);

        if (!empty($data['password'])) {
            // Ensure that password can be changed
            if (!$this->user()->canChangePasswordOf($user)) {
                throw new TranslatedException(sprintf('Cannot change the password of %s', $user->username()), 'panel.users.user.cannotChangePassword');
            }

            // Hash the new password
            $data['hash'] = Password::hash($data['password']);
        }

        // Remove password from $data
        unset($data['password']);

        // Ensure that user role can be changed
        if (($data['role'] ?? $user->role()) !== $user->role() && !$this->user()->canChangeRoleOf($user)) {
            throw new TranslatedException(sprintf('Cannot change the role of %s', $user->username()), 'panel.users.user.cannotChangeRole');
        }

        // Handle incoming files
        if ($this->request->files()->get('image')?->isUploaded() && ($image = $this->uploadImage($user, $this->request->files()->get('image'))) !== null) {
            $data['image'] = $image;
        }

        // Filter empty items from $data and merge them with $user ones
        $userData = [...$user->toArray(), ...$data];

        Yaml::encodeToFile($userData, FileSystem::joinPaths($this->config->get('system.panel.paths.accounts'), $user->username() . '.yaml'));

    }

    /**
     * Upload a new image for a user
     */
    protected function uploadImage(User $user, UploadedFile $file): ?string
    {
        $imagesPath = FileSystem::joinPaths($this->config->get('system.panel.paths.assets'), '/images/users/');

        $uploader = new Uploader($this->config);

        $uploadedFile = $uploader->upload($file, $imagesPath, FileSystem::randomName());

        if ($uploadedFile->type() === 'image') {
            $userImageSize = $this->config->get('system.panel.userImageSize');

            // Square off uploaded image
            $image = new Image($uploadedFile->path(), $this->config->get('system.images'));
            $image->square($userImageSize)->save();

            // Delete old image
            $this->deleteImage($user);

            $this->panel()->notify($this->translate('panel.user.image.uploaded'), 'success');
            return $uploadedFile->name();
        }

        return null;
    }

    /**
     * Delete the image of a given user
     */
    protected function deleteImage(User $user): void
    {
        $image = $user->image()->path();
        if (FileSystem::isFile($image, assertExists: false)) {
            FileSystem::delete($image);
        }
    }
}
