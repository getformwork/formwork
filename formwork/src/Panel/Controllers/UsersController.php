<?php

namespace Formwork\Panel\Controllers;

use Formwork\Exceptions\TranslatedException;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Files\FileUploader;
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
use Formwork\Schemes\Schemes;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\MimeType;
use RuntimeException;

class UsersController extends AbstractController
{
    /**
     * Users@index action
     */
    public function index(Schemes $schemes): Response
    {
        $this->ensurePermission('users.index');

        $this->modal('newUser', [
            'fields' => $schemes->get('modals.newUser')->fields(),
        ]);

        $this->modal('deleteUser');

        return new Response($this->view('users.index', [
            'title' => $this->translate('panel.users.users'),
            'users' => $this->panel()->users()->sortBy('username'),
        ]));
    }

    /**
     * Users@create action
     */
    public function create(Schemes $schemes): RedirectResponse
    {
        $this->ensurePermission('users.create');

        $requestData = $this->request->input();

        $fields = $schemes->get('modals.newUser')->fields();

        // Ensure no required data is missing
        try {
            $fields->setValues($requestData)->validate();
        } catch (ValidationException) {
            $this->panel()->notify($this->translate('panel.users.user.cannotCreate.varMissing'), 'error');
            return $this->redirect($this->generateRoute('panel.users'));
        }

        // Ensure there isn't a user with the same username
        if ($this->panel()->users()->has($requestData->get('username'))) {
            $this->panel()->notify($this->translate('panel.users.user.cannotCreate.alreadyExists'), 'error');
            return $this->redirect($this->generateRoute('panel.users'));
        }

        $userData = [
            'username' => $requestData->get('username'),
            'fullname' => $requestData->get('fullname'),
            'hash'     => Password::hash($requestData->get('password')),
            'email'    => $requestData->get('email'),
            'language' => $requestData->get('language'),
        ];

        Yaml::encodeToFile($userData, FileSystem::joinPaths($this->config->get('system.panel.paths.accounts'), $requestData->get('username') . '.yaml'));

        $this->panel()->notify($this->translate('panel.users.user.created'), 'success');
        return $this->redirect($this->generateRoute('panel.users'));
    }

    /**
     * Users@delete action
     */
    public function delete(RouteParams $routeParams): RedirectResponse
    {
        $this->ensurePermission('users.delete');

        $user = $this->panel()->users()->get($routeParams->get('user'));

        try {
            if (!$user) {
                throw new TranslatedException(sprintf('User "%s" not found', $routeParams->get('user')), 'panel.users.user.notFound');
            }
            if (!$this->user()->canDeleteUser($user)) {
                throw new TranslatedException(
                    sprintf('Cannot delete user "%s", you must be an administrator and the user must not be logged in', $user->username()),
                    'users.user.cannotDelete'
                );
            }
            FileSystem::delete(FileSystem::joinPaths($this->config->get('system.panel.paths.accounts'), $user->username() . '.yaml'));
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
    public function profile(RouteParams $routeParams): Response
    {
        $scheme = $this->app->schemes()->get('users.user');

        $fields = $scheme->fields();

        $user = $this->panel()->users()->get($routeParams->get('user'));

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
        $userData = [...$user->data(), ...$data];

        Yaml::encodeToFile($userData, FileSystem::joinPaths($this->config->get('system.panel.paths.accounts'), $user->username() . '.yaml'));
    }

    /**
     * Upload a new image for a user
     */
    protected function uploadImage(User $user, UploadedFile $file): ?string
    {
        $imagesPath = FileSystem::joinPaths($this->config->get('system.panel.paths.assets'), '/images/users/');

        $mimeTypes = Arr::map($this->config->get('system.files.allowedExtensions'), fn (string $ext) => MimeType::fromExtension($ext));

        $fileUploader = new FileUploader($mimeTypes);

        $uploadedFile = $fileUploader->upload($file, $imagesPath, FileSystem::randomName());

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

        if ($image === $this->panel->realUri('/assets/images/user-image.svg')) {
            throw new RuntimeException('Cannot delete default user image');
        }

        if (FileSystem::isFile($image, assertExists: false)) {
            FileSystem::delete($image);
        }
    }
}
