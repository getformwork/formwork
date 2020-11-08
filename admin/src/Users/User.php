<?php

namespace Formwork\Admin\Users;

use Formwork\Admin\Admin;
use Formwork\Admin\Security\Password;
use Formwork\Admin\Utils\Session;
use Formwork\Data\DataGetter;

class User extends DataGetter
{
    /**
     * Default data of the user
     *
     * @var array
     */
    protected $defaults = [
        'username' => null,
        'fullname' => null,
        'hash'     => null,
        'email'    => null,
        'language' => 'en',
        'role'     => 'user',
        'avatar'   => null
    ];

    /**
     * User username
     *
     * @var string
     */
    protected $username;

    /**
     * User full name
     *
     * @var string
     */
    protected $fullname;

    /**
     * User password hash
     *
     * @var string
     */
    protected $hash;

    /**
     * User email
     *
     * @var string
     */
    protected $email;

    /**
     * User language
     *
     * @var string
     */
    protected $language;

    /**
     * User role
     *
     * @var string
     */
    protected $role;

    /**
     * User avatar
     *
     * @var Avatar
     */
    protected $avatar;

    /**
     * User permissions
     *
     * @var Permissions
     */
    protected $permissions;

    /**
     * User last access time
     *
     * @var string|null
     */
    protected $lastAccess;

    /**
     * Create a new User instance
     */
    public function __construct(array $data)
    {
        parent::__construct(array_merge($this->defaults, $data));
        foreach (['username', 'fullname', 'hash', 'email', 'language', 'role'] as $var) {
            $this->$var = $this->data[$var];
        }

        $this->avatar = new Avatar($this->data['avatar']);
        $this->permissions = new Permissions($this->role);
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
     * Return user avatar
     */
    public function avatar(): Avatar
    {
        return $this->avatar;
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
        return Session::get('FORMWORK_USERNAME') === $this->username;
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
    public function lastAccess(): ?string
    {
        if ($this->lastAccess !== null) {
            return $this->lastAccess;
        }
        $lastAccess = Admin::instance()->registry('lastAccess')->get($this->username);
        return $this->lastAccess = $lastAccess;
    }

    /**
     * @inheritdoc
     *
     * @deprecated
     */
    public function get($key, $default = null)
    {
        trigger_error('Using ' . static::class . '::get() is deprecated since Formwork 1.10.0', E_USER_DEPRECATED);
        return parent::get($key, $default);
    }

    /**
     * @inheritdoc
     *
     * @deprecated
     */
    public function has($key): bool
    {
        trigger_error('Using ' . static::class . '::has() is deprecated since Formwork 1.10.0', E_USER_DEPRECATED);
        return parent::has($key);
    }

    public function __debugInfo(): array
    {
        $data = $this->data;
        // Unset hash to avoid exposure
        unset($data['hash']);
        return $data;
    }
}
