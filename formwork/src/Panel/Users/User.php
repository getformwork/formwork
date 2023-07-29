<?php

namespace Formwork\Panel\Users;

use Formwork\App;
use Formwork\Config;
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
     */
    protected array $data = [];

    /**
     * Default data of the user
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

        $filename = $this->data['image'];

        $path = FileSystem::joinPaths($this->config->get('system.panel.paths.assets'), 'images/users/', $filename);

        if ($filename !== null && FileSystem::exists($path)) {
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
     * Return whether the user has 'panel' role
     */
    public function isPanel(): bool
    {
        return $this->role === 'panel';
    }

    /**
     * Return whether the user can delete a given user
     */
    public function canDeleteUser(User $user): bool
    {
        return $this->isPanel() && !$user->isLogged();
    }

    /**
     * Return whether the user can change options of a given user
     */
    public function canChangeOptionsOf(User $user): bool
    {
        return $this->isPanel() || $user->isLogged();
    }

    /**
     * Return whether the user can change the password of a given user
     */
    public function canChangePasswordOf(User $user): bool
    {
        return $this->isPanel() || $user->isLogged();
    }

    /**
     * Return whether the user can change the role of a given user
     */
    public function canChangeRoleOf(User $user): bool
    {
        return $this->isPanel() && !$user->isLogged();
    }

    /**
     * Get the user last access time
     */
    public function lastAccess(): ?int
    {
        if (isset($this->lastAccess)) {
            return $this->lastAccess;
        }
        $lastAccessRegistry = new Registry($this->config->get('system.panel.paths.logs') . 'lastAccess.json');
        $lastAccess = (int) $lastAccessRegistry->get($this->username);
        return $this->lastAccess = $lastAccess ?: null;
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
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
