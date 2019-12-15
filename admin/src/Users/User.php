<?php

namespace Formwork\Admin\Users;

use Formwork\Admin\Admin;
use Formwork\Admin\Security\Password;
use Formwork\Admin\Utils\Session;
use Formwork\Data\DataGetter;
use LogicException;

class User extends DataGetter
{
    /**
     * Default data of the user
     *
     * @var array
     */
    protected $defaults = [
        'avatar' => null,
        'role'   => 'user'
    ];

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
     * @var string
     */
    protected $lastAccess;

    /**
     * Create a new User instance
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct(array_merge($this->defaults, $data));
        $this->avatar = new Avatar($this->data['avatar']);
        $this->permissions = new Permissions($this->data['role']);
    }

    /**
     * Return whether a given password authenticates the user
     *
     * @param string $password
     *
     * @return bool
     */
    public function authenticate(string $password)
    {
        return Password::verify($password, $this->data['hash']);
    }

    /**
     * Return whether the user is logged or not
     *
     * @return bool
     */
    public function isLogged()
    {
        return Session::get('FORMWORK_USERNAME') === $this->data['username'];
    }

    /**
     * Return whether the user has 'admin' role
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->data['role'] === 'admin';
    }

    /**
     * Return whether the user can delete a given user
     *
     * @param User $user
     *
     * @return bool
     */
    public function canDeleteUser(User $user)
    {
        return $this->isAdmin() && !$user->isLogged();
    }

    /**
     * Return whether the user can change options of a given user
     *
     * @param User $user
     *
     * @return bool
     */
    public function canChangeOptionsOf(User $user)
    {
        return $this->isAdmin() || $user->isLogged();
    }

    /**
     * Return whether the user can change the password of a given user
     *
     * @param User $user
     *
     * @return bool
     */
    public function canChangePasswordOf(User $user)
    {
        return $this->isAdmin() || $user->isLogged();
    }

    /**
     * Return whether the user can change the role of a given user
     *
     * @param User $user
     *
     * @return bool
     */
    public function canChangeRoleOf(User $user)
    {
        return $this->isAdmin() && !$user->isLogged();
    }

    /**
     * Get the user last access time
     *
     * @return string
     */
    public function lastAccess()
    {
        if ($this->lastAccess !== null) {
            return $this->lastAccess;
        }
        $lastAccess = Admin::instance()->registry('lastAccess')->get($this->data['username']);
        return $this->lastAccess = $lastAccess;
    }

    public function __call(string $name, array $arguments)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        if ($this->has($name)) {
            return $this->get($name);
        }
        throw new LogicException('Invalid method ' . static::class . '::' . $name);
    }
}
