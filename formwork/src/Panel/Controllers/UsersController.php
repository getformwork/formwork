<?php

namespace Formwork\Panel\Controllers;

use Formwork\Exceptions\TranslatedException;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\FieldCollection;
use Formwork\Files\FileUploader;
use Formwork\Http\FileResponse;
use Formwork\Http\Files\UploadedFile;
use Formwork\Http\RedirectResponse;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Images\Image;
use Formwork\Log\Registry;
use Formwork\Panel\Security\Password;
use Formwork\Parsers\Yaml;
use Formwork\Router\RouteParams;
use Formwork\Schemes\Schemes;
use Formwork\Users\User;
use Formwork\Utils\Arr;
use Formwork\Utils\Exceptions\FileNotFoundException;
use Formwork\Utils\FileSystem;
use RuntimeException;

class UsersController extends AbstractController
{
    /**
     * Users@index action
     */
    public function index(Schemes $schemes): Response
    {
        $this->ensurePermission('users.index');

        $this->modal('newUser');

        $this->modal('deleteUser');

        return new Response($this->view('users.index', [
            'title' => $this->translate('panel.users.users'),
            'users' => $this->site->users()->sortBy('username'),
        ]));
    }

    /**
     * Users@create action
     */
    public function create(Schemes $schemes): RedirectResponse
    {
        $this->ensurePermission('users.create');

        $requestData = $this->request->input();

        $fields = $this->modal('newUser')->fields();

        // Ensure no required data is missing
        try {
            $fields->setValues($requestData)->validate();
        } catch (ValidationException) {
            $this->panel()->notify($this->translate('panel.users.user.cannotCreate.varMissing'), 'error');
            return $this->redirect($this->generateRoute('panel.users'));
        }

        // Ensure there isn't a user with the same username
        if ($this->site->users()->has($requestData->get('username'))) {
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

        Yaml::encodeToFile($userData, FileSystem::joinPaths($this->config->get('system.users.paths.accounts'), $requestData->get('username') . '.yaml'));

        $this->panel()->notify($this->translate('panel.users.user.created'), 'success');
        return $this->redirect($this->generateRoute('panel.users'));
    }

    /**
     * Users@delete action
     */
    public function delete(RouteParams $routeParams): RedirectResponse
    {
        $this->ensurePermission('users.delete');

        $user = $this->site->users()->get($routeParams->get('user'));

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
            FileSystem::delete(FileSystem::joinPaths($this->config->get('system.users.paths.accounts'), $user->username() . '.yaml'));
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

        $user = $this->site->users()->get($routeParams->get('user'));

        if ($user === null) {
            $this->panel()->notify($this->translate('panel.users.user.notFound'), 'error');
            return $this->redirect($this->generateRoute('panel.users'));
        }

        $fields->setModel($user);

        // Disable password and/or role fields if they cannot be changed
        $fields->get('password')->set('disabled', !$this->user()->canChangePasswordOf($user));
        $fields->get('role')->set('disabled', !$this->user()->canChangeRoleOf($user));

        if ($this->request->method() === RequestMethod::POST) {
            // Ensure that options can be changed
            if ($this->user()->canChangeOptionsOf($user)) {
                $fields->setValuesFromRequest($this->request, null)->validate();

                try {
                    $this->updateUser($user, $fields);
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

    public function images(RouteParams $routeParams): Response
    {
        $path = FileSystem::joinPaths($this->config->get('system.users.paths.images'), $routeParams->get('image'));

        if (FileSystem::isFile($path)) {
            return new FileResponse($path);
        }

        throw new FileNotFoundException('Cannot find asset');
    }

    /**
     * Update user data from POST request
     */
    protected function updateUser(User $user, FieldCollection $fieldCollection): void
    {
        $userData = $user->data();

        foreach ($fieldCollection as $field) {
            if ($field->isEmpty()) {
                continue;
            }

            if ($field->name() === 'password') {
                // Ensure that password can be changed
                if (!$this->user()->canChangePasswordOf($user)) {
                    throw new TranslatedException(sprintf('Cannot change the password of %s', $user->username()), 'panel.users.user.cannotChangePassword');
                }
                // Hash the new password
                Arr::set($userData, 'hash', Password::hash($field->value()));
                continue;
            }

            if ($field->name() === 'role') {
                // Ensure that user role can be changed
                if ($field->value() !== $user->role() && !$this->user()->canChangeRoleOf($user)) {
                    throw new TranslatedException(sprintf('Cannot change the role of %s', $user->username()), 'panel.users.user.cannotChangeRole');
                }
                Arr::set($userData, 'role', $field->value());
                continue;
            }

            if ($field->name() === 'image') {
                $file = $field->value();
                // Handle incoming files
                if ($file && ($image = $this->uploadImage($user, $file, $field->acceptMimeTypes())) !== null) {
                    Arr::set($userData, 'image', $image);
                }
                continue;
            }

            Arr::set($userData, $field->name(), $field->value());
        }

        Yaml::encodeToFile($userData, FileSystem::joinPaths($this->config->get('system.users.paths.accounts'), $user->username() . '.yaml'));
    }

    /**
     * Upload a new image for a user
     *
     * @param array<string> $mimeTypes
     */
    protected function uploadImage(User $user, UploadedFile $file, array $mimeTypes): ?string
    {
        $imagesPath = FileSystem::joinPaths($this->config->get('system.users.paths.images'));

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
