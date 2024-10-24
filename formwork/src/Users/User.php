<?php

namespace Formwork\Users;

use Formwork\App;
use Formwork\Config\Config;
use Formwork\Files\FileFactory;
use Formwork\Http\Request;
use Formwork\Images\Image;
use Formwork\Log\Registry;
use Formwork\Model\Model;
use Formwork\Panel\Security\Password;
use Formwork\Users\Exceptions\AuthenticationFailedException;
use Formwork\Users\Exceptions\UserImageNotFoundException;
use Formwork\Users\Exceptions\UserNotLoggedException;
use Formwork\Utils\FileSystem;
use SensitiveParameter;

class User extends Model
{
    public const SESSION_LOGGED_USER_KEY = '_formwork_logged_user';

    protected const MODEL_IDENTIFIER = 'user';

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
    protected Image $image;

    /**
     * User last access time
     */
    protected ?int $lastAccess = null;

    /**
     * Create a new User instance
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data, protected Role $role, protected App $app, protected Config $config, protected Request $request, protected FileFactory $fileFactory)
    {
        $this->scheme = $app->schemes()->get('users.user');

        $this->fields = $this->scheme->fields();
        $this->fields->setModel($this);

        $this->data = [...$this->defaults, ...$data];

        $this->fields->setValues($this->data);
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
     */
    public function image(): Image
    {
        if (isset($this->image)) {
            return $this->image;
        }

        $path = FileSystem::joinPaths($this->config->get('system.users.paths.images'), (string) $this->data['image']);

        if (!FileSystem::isFile($path, assertExists: false)) {
            $path = FileSystem::joinPaths($this->config->get('system.panel.paths.assets'), 'images/user-image.svg');
        }

        $file = $this->fileFactory->make($path);

        if (!($file instanceof Image)) {
            throw new UserImageNotFoundException('Invalid user image');
        }

        return $this->image = $file;
    }

    public function hasDefaultImage(): bool
    {
        return $this->image()->path() === FileSystem::joinPaths($this->config->get('system.panel.paths.assets'), 'images/user-image.svg');
    }

    public function role(): Role
    {
        return $this->role;
    }

    public function colorScheme(): ColorScheme
    {
        return ColorScheme::from($this->data['colorScheme']);
    }

    /**
     * Return user permissions
     */
    public function permissions(): Permissions
    {
        return $this->role->permissions();
    }

    /**
     * Authenticate the user
     */
    public function authenticate(
        #[SensitiveParameter]
        string $password
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
        string $password
    ): bool {
        return Password::verify($password, $this->hash());
    }

    /**
     * Log out the user
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
        if ($this->isAdmin()) {
            return true;
        }
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
}
