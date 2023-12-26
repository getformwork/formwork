<?php

namespace Formwork\Panel\Users;

use Formwork\App;
use Formwork\Config\Config;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Http\Request;
use Formwork\Log\Registry;
use Formwork\Panel\Panel;
use Formwork\Panel\Security\Password;
use Formwork\Utils\FileSystem;

class User implements Arrayable
{
    /**
     * Array containing user data
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

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
     * User username
     */
    protected string $username;

    /**
     * User full name
     */
    protected string $fullname;

    /**
     * User password hash
     */
    protected string $hash;

    /**
     * User email
     */
    protected string $email;

    /**
     * User language
     */
    protected string $language;

    /**
     * User role
     */
    protected string $role;

    /**
     * User image
     */
    protected UserImage $image;

    /**
     * User permissions
     */
    protected Permissions $permissions;

    /**
     * User last access time
     */
    protected ?int $lastAccess;

    /**
     * Create a new User instance
     *
     * @param array<string, mixed> $data
     * @param array<string, bool>  $permissions
     */
    public function __construct(array $data, array $permissions, protected App $app, protected Config $config, protected Request $request, protected Panel $panel)
    {
        $this->data = [...$this->defaults, ...$data];
        foreach (['username', 'fullname', 'hash', 'email', 'language', 'role'] as $var) {
            $this->{$var} = $this->data[$var];
        }

        $this->permissions = new Permissions($permissions);
    }

    public function __debugInfo(): array
    {
        $data = $this->data;
        // Unset hash to avoid exposure
        unset($data['hash']);
        return $data;
    }

    /**
     * Return the username
     */
    public function username(): string
    {
        return $this->username;
    }

    /**
     * Return the full name
     */
    public function fullname(): string
    {
        return $this->fullname;
    }

    /**
     * Return the email
     */
    public function email(): string
    {
        return $this->email;
    }

    /**
     * Return the language code
     */
    public function language(): string
    {
        return $this->language;
    }

    /**
     * Return the role
     */
    public function role(): string
    {
        return $this->role;
    }

    /**
     * Return user image
     */
    public function image(): UserImage
    {
        if (isset($this->image)) {
            return $this->image;
        }

        $filename = (string) $this->data['image'];

        $path = FileSystem::joinPaths($this->config->get('system.panel.paths.assets'), 'images/users/', $filename);

        if (FileSystem::isFile($path, assertExists: false)) {
            $uri = $this->panel->realUri('/assets/images/users/' . basename($path));
            $path = $path;
        } else {
            $uri = $this->panel->realUri('/assets/images/user-image.svg');
        }

        return $this->image = new UserImage($path, $uri);
    }

    /**
     * Return user permissions
     */
    public function permissions(): Permissions
    {
        return $this->permissions;
    }

    /**
     * Return whether a given password authenticates the user
     */
    public function authenticate(string $password): bool
    {
        return Password::verify($password, $this->hash);
    }

    /**
     * Return whether the user is logged or not
     */
    public function isLogged(): bool
    {
        return $this->request->session()->get('FORMWORK_USERNAME') === $this->username;
    }

    /**
     * Return whether the user has 'admin' role
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Return whether the user can delete a given user
     */
    public function canDeleteUser(User $user): bool
    {
        return $this->isAdmin() && !$user->isLogged();
    }

    /**
     * Return whether the user can change options of a given user
     */
    public function canChangeOptionsOf(User $user): bool
    {
        return $this->isAdmin() || $user->isLogged();
    }

    /**
     * Return whether the user can change the password of a given user
     */
    public function canChangePasswordOf(User $user): bool
    {
        return $this->isAdmin() || $user->isLogged();
    }

    /**
     * Return whether the user can change the role of a given user
     */
    public function canChangeRoleOf(User $user): bool
    {
        return $this->isAdmin() && !$user->isLogged();
    }

    /**
     * Get the user last access time
     */
    public function lastAccess(): ?int
    {
        if (isset($this->lastAccess)) {
            return $this->lastAccess;
        }
        $registry = new Registry(FileSystem::joinPaths($this->config->get('system.panel.paths.logs'), 'lastAccess.json'));
        return $this->lastAccess = $registry->has($this->username) ? (int) $registry->get($this->username) : null;
    }

    /**
     * Get the user color scheme preference
     */
    public function colorScheme(): string
    {
        return $this->data['colorScheme'];
    }

    /**
     * Return an array containing user data
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
