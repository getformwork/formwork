<?php

namespace Formwork\Panel\Controllers;

use Formwork\Exceptions\TranslatedException;
use Formwork\Fields\Field;
use Formwork\Forms\FormData;
use Formwork\Http\FileResponse;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Images\Image;
use Formwork\Log\Registry;
use Formwork\Panel\Security\Password;
use Formwork\Parsers\Yaml;
use Formwork\Router\RouteParams;
use Formwork\Users\User;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;

final class UsersController extends AbstractController
{
    /**
     * Users@index action
     */
    public function index(): Response
    {
        if (!$this->hasPermission('panel.users.index')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        return new Response($this->view('@panel.users.index', [
            'title' => $this->translate('panel.users.users'),
            'users' => $this->site->users()->sortBy('username'),
        ]));
    }

    /**
     * Users@create action
     */
    public function create(): Response
    {
        if (!$this->hasPermission('panel.users.create')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $fields = $this->modal('newUser')->fields();

        $form = $this->form('new-user', $fields)
            ->processRequest($this->request);

        if (!$form->isValid()) {
            $this->panel->notify($this->translate('panel.users.user.cannotCreate.invalidFields'), 'error');
            return $this->redirect($this->generateRoute('panel.users'));
        }

        $data = $form->data();

        $username = $data->get('username');

        // Ensure there isn't a user with the same username
        if ($this->site->users()->has($username)) {
            $this->panel->notify($this->translate('panel.users.user.cannotCreate.alreadyExists'), 'error');
            return $this->redirect($this->generateRoute('panel.users'));
        }

        $email = $data->get('email');

        // Ensure email is not already used by another user
        if ($this->site->users()->filterBy('email', $email)->count() > 0) {
            $this->panel->notify($this->translate('panel.users.user.cannotCreate.emailAlreadyUsed'), 'error');
            return $this->redirect($this->generateRoute('panel.users'));
        }

        Yaml::encodeToFile([
            'username' => $username,
            'fullname' => $data->get('fullname'),
            'hash'     => Password::hash($data->get('password')),
            'email'    => $data->get('email'),
            'language' => $data->get('language'),
            'role'     => $data->get('role'),
        ], FileSystem::joinPaths($this->config->get('system.users.paths.accounts'), $username . '.yaml'));

        $this->panel->notify($this->translate('panel.users.user.created'), 'success');
        return $this->redirect($this->generateRoute('panel.users'));
    }

    /**
     * Users@delete action
     */
    public function delete(RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('panel.users.delete')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $user = $this->site->users()->get($routeParams->get('user'));

        try {
            if (!$user) {
                throw new TranslatedException(sprintf('User "%s" not found', $routeParams->get('user')), 'panel.users.user.notFound');
            }
            if (!$this->panel->user()->canDeleteUser($user)) {
                throw new TranslatedException(
                    sprintf('Cannot delete user "%s", you must be an administrator and the user must not be logged in', $user->username()),
                    'users.user.cannotDelete'
                );
            }
            FileSystem::delete(FileSystem::joinPaths($this->config->get('system.users.paths.accounts'), $user->username() . '.yaml'));

            if ($user->image() !== null) {
                $this->deleteUserImage($user);
            }
        } catch (TranslatedException $e) {
            $this->panel->notify($this->translate($e->getLanguageString()), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.users'), base: $this->panel->panelRoot());
        }

        $lastAccessRegistry = new Registry(FileSystem::joinPaths($this->config->get('system.panel.paths.logs'), 'lastAccess.json'));

        // Remove user last access from registry
        $lastAccessRegistry->remove($user->username());

        $this->panel->notify($this->translate('panel.users.user.deleted'), 'success');
        return $this->redirect($this->generateRoute('panel.users'));
    }

    /**
     * Users@deleteImage action
     */
    public function deleteImage(RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('panel.users.deleteImage')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $user = $this->site->users()->get($routeParams->get('user'));

        if ($user === null) {
            $this->panel->notify($this->translate('panel.users.user.notFound'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.users'), base: $this->panel->panelRoot());
        }

        if ($this->panel->user()->canChangeOptionsOf($user)) {
            try {
                $this->deleteUserImage($user);

                $userData = $user->data();
                Arr::remove($userData, 'image');
                Yaml::encodeToFile($userData, FileSystem::joinPaths($this->config->get('system.users.paths.accounts'), $user->username() . '.yaml'));

                $this->panel->notify($this->translate('panel.user.image.deleted'), 'success');
            } catch (TranslatedException $e) {
                $this->panel->notify($this->translate($e->getLanguageString()), 'error');
            }
        } else {
            $this->panel->notify($this->translate('panel.users.user.cannotEdit', $user->username()), 'error');
        }

        return $this->redirect($this->generateRoute('panel.users.profile', ['user' => $user->username()]));
    }

    /**
     * Users@profile action
     */
    public function profile(RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('panel.users.profile')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $scheme = $this->app->schemes()->get('users.user');

        $fields = $scheme->fields();

        $user = $this->site->users()->get($routeParams->get('user'));

        if ($user === null) {
            $this->panel->notify($this->translate('panel.users.user.notFound'), 'error');
            return $this->redirect($this->generateRoute('panel.users'));
        }

        $fields->setModel($user);

        // Hide password field if the user cannot change it
        $fields->get('password')->set('visible', $this->panel->user()->canChangePasswordOf($user));

        // Disable role field if it cannot be changed
        $fields->get('role')->set('disabled', !$this->panel->user()->canChangeRoleOf($user));

        // Set initial values on GET
        if ($this->request->method() === RequestMethod::GET) {
            $fields->setValues($user)
                ->isValid(); // Pre-validate to populate validation state
        }

        $form = $this->form('user-profile', $fields)
            ->processRequest($this->request, uploadFiles: false, preserveEmpty: false);

        if ($form->isSubmitted()) {
            // Ensure that options can be changed
            if (!$this->panel->user()->canChangeOptionsOf($user)) {
                $this->panel->notify($this->translate('panel.users.user.cannotEdit', $user->username()), 'error');
            } elseif (!$form->isValid()) {
                $this->panel->notify($this->translate('panel.users.user.cannotEdit.invalidFields'), 'error');
            } else {
                try {
                    // Handle image upload separately
                    $image = null;
                    if (($imageField = $form->fields()->get('image')) && !$imageField->isEmpty()) {
                        $image = $this->uploadUserImage($user, $imageField);
                    }

                    $this->updateUser($user, $form->data(), $image);
                    $this->panel->notify($this->translate('panel.users.user.edited'), 'success');
                } catch (TranslatedException $e) {
                    $this->panel->notify($this->translate($e->getLanguageString(), $user->username()), 'error');
                }
            }

            return $this->redirect($this->generateRoute('panel.users.profile', ['user' => $user->username()]));
        }

        return new Response($this->view('@panel.users.profile', [
            'title'  => $this->translate('panel.users.userProfile', $user->username()),
            'user'   => $user,
            'fields' => $form->fields(),
            ...$this->getPreviousAndNextUser($user),
        ]), $form->getResponseStatus());
    }

    /**
     * Users@images action
     */
    public function images(RouteParams $routeParams): Response
    {
        $path = FileSystem::joinPaths($this->config->get('system.users.paths.images'), $routeParams->get('image'));

        if (FileSystem::isFile($path, assertExists: false)) {
            return new FileResponse($path, headers: ['Cache-Control' => 'private, max-age=31536000, immutable'], autoEtag: true, autoLastModified: true);
        }

        return $this->forward(ErrorsController::class, 'notFound');
    }

    /**
     * Update user data from POST request
     */
    private function updateUser(User $user, FormData $formData, ?Image $image = null): void
    {
        $userData = $user->data();

        // Validate email uniqueness
        if ($formData->has('email') && $formData->get('email') !== $user->email() && $this->site->users()->filterBy('email', $formData->get('email'))->count() > 0) {
            throw new TranslatedException(sprintf('Cannot change the email of %s, the address is already used', $user->username()), 'panel.users.user.cannotChangeEmail.alreadyUsed');
        }

        // Handle password change
        if ($formData->has('password')) {
            // Ensure that password can be changed
            if (!$this->panel->user()->canChangePasswordOf($user)) {
                throw new TranslatedException(sprintf('Cannot change the password of %s', $user->username()), 'panel.users.user.cannotChangePassword');
            }
            // Hash the new password
            Arr::set($userData, 'hash', Password::hash($formData->get('password')));
        }

        // Handle role change
        if ($formData->has('role') && $formData->get('role') !== $user->role()) {
            // Ensure that user role can be changed
            if (!$this->panel->user()->canChangeRoleOf($user)) {
                throw new TranslatedException(sprintf('Cannot change the role of %s', $user->username()), 'panel.users.user.cannotChangeRole');
            }
            Arr::set($userData, 'role', $formData->get('role'));
        }

        // Handle uploaded image
        if ($image !== null) {
            Arr::set($userData, 'image', $image->name());
        }

        // Merge remaining form data (excluding password, role, and image which were already handled)
        foreach ($formData->toArray() as $key => $value) {
            if (!in_array($key, ['password', 'role', 'image'], true)) {
                Arr::set($userData, $key, $value);
            }
        }

        Yaml::encodeToFile($userData, FileSystem::joinPaths($this->config->get('system.users.paths.accounts'), $user->username() . '.yaml'));
    }

    /**
     * Upload a new image for a user
     */
    private function uploadUserImage(User $user, Field $field): ?Image
    {
        $imagesPath = FileSystem::joinPaths($this->config->get('system.users.paths.images'));

        $files = $field->isMultiple() ? $field->value() : [$field->value()];

        if ($files === []) {
            return null;
        }

        $file = $this->fileUploader->upload(
            $files[0],
            $imagesPath,
            FileSystem::randomName(),
            $field->acceptMimeTypes(),
        );

        if (!($file instanceof Image)) {
            return null;
        }

        $userImageSize = $this->config->get('system.panel.userImageSize');

        // Square off uploaded image
        $file->square($userImageSize)->save();

        // Delete old image
        if ($user->image() !== null) {
            $this->deleteUserImage($user);
        }

        $this->panel->notify($this->translate('panel.user.image.uploaded'), 'success');

        return $file;
    }

    /**
     * Delete the image of a given user
     */
    private function deleteUserImage(User $user): void
    {
        if ($user->image() === null) {
            throw new TranslatedException('Cannot delete default user image', 'panel.user.image.cannotDelete.defaultImage');
        }

        $path = $user->image()->path();

        if (FileSystem::isFile($path, assertExists: false)) {
            FileSystem::delete($path);
        }
    }

    /**
     * Get previous and next user of a given user
     *
     * @return array{previousUser: ?User, nextUser: ?User}
     */
    private function getPreviousAndNextUser(User $user): array
    {
        $users = $this->site->users()->sortBy('username');

        $userIndex = $users->indexOf($user);

        return [
            'previousUser' => $users->nth($userIndex - 1),
            'nextUser'     => $users->nth($userIndex + 1),
        ];
    }
}
