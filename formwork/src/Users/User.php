<?php

namespace Formwork\Users;

use Formwork\Config\Config;
use Formwork\Data\Exceptions\InvalidValueException;
use Formwork\Exceptions\TranslatedException;
use Formwork\Files\FileFactory;
use Formwork\Http\Request;
use Formwork\Images\Image;
use Formwork\Log\Registry;
use Formwork\Model\Model;
use Formwork\Parsers\Yaml;
use Formwork\Schemes\Schemes;
use Formwork\Users\Exceptions\AuthenticationFailedException;
use Formwork\Users\Exceptions\UserImageNotFoundException;
use Formwork\Users\Exceptions\UserNotLoggedException;
use Formwork\Users\Utils\Password;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use LogicException;
use SensitiveParameter;

class User extends Model
{
    public const string SESSION_LOGGED_USER_KEY = '_formwork_logged_user';

    public const int MINIMUM_PASSWORD_LENGTH = 8;

    protected const string MODEL_IDENTIFIER = 'user';

    /**
     * Default data of the user
     *
     * @var array<string, mixed>
     */
    protected array $defaults = [
        'username'    => null,
        'fullname'    => null,
        'hash'        => null,
        'email'       => null,
        'language'    => 'en',
        'role'        => 'user',
        'image'       => null,
        'colorScheme' => 'auto',
    ];

    /**
     * User image
     */
    protected ?Image $image = null;

    /**
     * User last access time
     */
    protected ?int $lastAccess = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        array $data,
        protected Schemes $schemes,
        protected Config $config,
        protected Request $request,
        protected FileFactory $fileFactory,
        protected Users $users,
    ) {
        $this->data = Arr::override($this->defaults, Arr::undot($data));

        $this->load();
    }

    public function __debugInfo(): array
    {
        $data = $this->data;
        // Unset hash to avoid exposure
        unset($data['hash']);
        return $data;
    }

    /**
     * Return user image
     *
     * @throws UserImageNotFoundException If the user image file is not a valid image
     */
    public function image(): ?Image
    {
        if ($this->image !== null) {
            return $this->image;
        }

        $path = FileSystem::joinPaths($this->config->get('system.users.paths.images'), (string) ($this->data['image'] ?? null));

        if (!FileSystem::isFile($path, assertExists: false)) {
            return $this->image = null;
        }

        $file = $this->fileFactory->make($path);

        if (!($file instanceof Image)) {
            throw new UserImageNotFoundException('Invalid user image');
        }

        return $this->image = $file;
    }

    /**
     * Return user role
     */
    public function role(): Role
    {
        if (!isset($this->data['role'])) {
            throw new LogicException(sprintf('User "%s" has no role assigned', $this->username()));
        }

        $role = $this->data['role'];

        if (!$this->users->roles()->has($role)) {
            throw new LogicException(sprintf('User "%s" has an invalid role assigned: "%s"', $this->username(), $role));
        }

        return $this->users->roles()->get($role);
    }

    /**
     * Return user color scheme
     */
    public function colorScheme(): ColorScheme
    {
        return ColorScheme::from($this->data['colorScheme']);
    }

    /**
     * Return user permissions
     */
    public function permissions(): Permissions
    {
        return $this->role()->permissions();
    }

    /**
     * Authenticate the user
     *
     * @throws AuthenticationFailedException If the password verification fails
     */
    public function authenticate(
        #[SensitiveParameter]
        string $password,
    ): void {
        if (!$this->verifyPassword($password)) {
            throw new AuthenticationFailedException(sprintf('Authentication failed for user "%s"', $this->username()));
        }
        $this->request->session()->regenerate();
        $this->request->session()->set(self::SESSION_LOGGED_USER_KEY, $this->username());
    }

    /**
     * Return whether a given password authenticates the user
     */
    public function verifyPassword(
        #[SensitiveParameter]
        string $password,
    ): bool {
        return Password::verify($password, $this->hash());
    }

    /**
     * Log out the user
     *
     * @throws UserNotLoggedException If the user is not currently logged in
     */
    public function logout(): void
    {
        if (!$this->isLoggedIn()) {
            throw new UserNotLoggedException(sprintf('Cannot logout user "%s": user not logged', $this->username()));
        }
        $this->request->session()->remove(self::SESSION_LOGGED_USER_KEY);
        $this->request->session()->destroy();
    }

    /**
     * Return whether the user is logged or not
     */
    public function isLoggedIn(): bool
    {
        return $this->request->session()->get(self::SESSION_LOGGED_USER_KEY) === $this->username();
    }

    /**
     * Return whether the user has 'admin' role
     */
    public function isAdmin(): bool
    {
        return $this->role()->id() === 'admin';
    }

    /**
     * Return whether the user can delete a given user
     */
    public function canDeleteUser(User $user): bool
    {
        return $this->isAdmin() && !$user->isLoggedIn();
    }

    /**
     * Return whether the user can change options of a given user
     */
    public function canChangeOptionsOf(User $user): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        return $user->isLoggedIn();
    }

    /**
     * Return whether the user can change the password of a given user
     */
    public function canChangePasswordOf(User $user): bool
    {
        return $user->isLoggedIn();
    }

    /**
     * Return whether the user can change the role of a given user
     */
    public function canChangeRoleOf(User $user): bool
    {
        return $this->isAdmin() && !$user->isLoggedIn();
    }

    /**
     * Get the user last access time
     */
    public function lastAccess(): ?int
    {
        if ($this->lastAccess !== null) {
            return $this->lastAccess;
        }
        $registry = new Registry(FileSystem::joinPaths($this->config->get('system.panel.paths.logs'), 'lastAccess.json'));
        return $this->lastAccess = $registry->has($this->username()) ? (int) $registry->get($this->username()) : null;
    }

    /**
     * Set a data value by key
     *
     * * When setting the `username` key, an exception is thrown if the user
     * already has a username assigned.
     * * When setting the `password` key, the password is hashed and the plain value
     * is not stored in the internal data array.
     * * When setting the `email` key, the value is validated before being stored.
     * * When setting the `role` key, the value is validated before being stored.
     *
     * This method updates both the data array and the corresponding field
     * (if it exists). The field's validation may transform the value before
     * it's stored in the data array.
     */
    public function set(string $key, mixed $value): void
    {
        if ($key === 'username' && $this->get('username') !== null) {
            throw new LogicException('Cannot change username of an existing user');
        }

        if ($key === 'password') {
            $this->setPasswordHash((string) $value);

            // Do not store plain password in data array
            return;
        }

        if ($key === 'email') {
            $this->validateEmail((string) $value);
        }

        if ($key === 'role') {
            $this->validateRole((string) $value);
        }

        parent::set($key, $value);
    }

    /**
     * Save user data to file
     */
    public function save(): void
    {
        if ($this->username() === null) {
            throw new LogicException('Cannot save a user with no username assigned');
        }

        Yaml::encodeToFile($this->data, FileSystem::joinPaths($this->config->get('system.users.paths.accounts'), $this->username() . '.yaml'));
    }

    /**
     * Delete the user account file and image
     */
    public function delete(): void
    {
        if ($this->username() === null) {
            throw new LogicException('Cannot delete a user with no username assigned');
        }

        // Delete user file
        FileSystem::delete(FileSystem::joinPaths($this->config->get('system.users.paths.accounts'), $this->username() . '.yaml'));

        // Delete user image if exists
        if ($this->image() !== null) {
            $this->deleteImageFile();
        }
    }

    /**
     * Delete the user image
     *
     * @throws TranslatedException If the user has no image
     */
    public function deleteImage(): void
    {
        $this->deleteImageFile();

        Arr::remove($this->data, 'image');
        $this->save();
    }

    /**
     * Load user scheme and fields
     */
    protected function load(): void
    {
        $this->scheme = $this->schemes->get('users.user');

        $this->fields = $this->scheme->fields();
        $this->fields->setModel($this);

        $this->fields->setValues($this->data);
    }

    /**
     * Set user image
     */
    protected function setImage(string|Image|null $image): void
    {
        if ($image instanceof Image) {
            $imagesPath = FileSystem::joinPaths($this->config->get('system.users.paths.images'));

            if (!Str::startsWith($image->path(), $imagesPath)) {
                throw new LogicException('User image must be located in the user images directory');
            }

            $image = $image->name();
        }

        // Delete old image if exists
        if ($this->image() !== null && ($image === null || $image !== $this->image()->name())) {
            $this->deleteImageFile();
        }

        Arr::set($this->data, 'image', $image);
    }

    /**
     * Delete the user image file
     *
     * @throws TranslatedException If the user has no image
     */
    protected function deleteImageFile(): void
    {
        if ($this->image() === null) {
            throw new TranslatedException('Cannot delete default user image', 'panel.user.image.cannotDelete.defaultImage');
        }

        $path = $this->image()->path();

        if (FileSystem::isFile($path, assertExists: false)) {
            FileSystem::delete($path);
        }
    }

    /**
     * Validate user email
     *
     * @throws TranslatedException   If email is already used by another user
     * @throws InvalidValueException If the e-mail address is not valid
     */
    protected function validateEmail(string $email): void
    {
        if ($email !== $this->email() && $this->users->some(fn(User $user) => $user->email() === $email)) {
            throw new TranslatedException(sprintf('Cannot change the email of %s, the address is already used', $this->username()), 'panel.users.user.cannotChangeEmail.alreadyUsed');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidValueException(sprintf('Invalid e-mail address "%s"', $email));
        }
    }

    /**
     * Validate user role
     *
     * @throws InvalidValueException If the role does not exist
     */
    protected function validateRole(string $role): void
    {
        if (!$this->users->roles()->has($role)) {
            throw new InvalidValueException(sprintf('Role "%s" does not exist', $role));
        }
    }

    /**
     * Set user password hash
     *
     * @throws InvalidValueException If the password is too short
     */
    protected function setPasswordHash(string $password): void
    {
        if (strlen($password) < self::MINIMUM_PASSWORD_LENGTH) {
            throw new InvalidValueException(sprintf('Password must be at least %d characters long', self::MINIMUM_PASSWORD_LENGTH));
        }
        Arr::set($this->data, 'hash', Password::hash($password));
    }
}
