<?php

namespace Formwork\Admin\Users;

use Formwork\Admin\Admin;
use Formwork\Admin\Security\Password;
use Formwork\Admin\Utils\Session;
use Formwork\Data\DataGetter;
use LogicException;

class User extends DataGetter
{
    protected $defaults = array(
        'avatar' => null,
        'role' => 'user'
    );

    protected $avatar;

    protected $permissions;

    protected $lastAccess;

    public function __construct($data)
    {
        $this->data = array_merge($this->defaults, $data);
        $this->avatar = new Avatar($this->data['avatar']);
        $this->permissions = new Permissions($this->data['role']);
    }

    public function authenticate($password)
    {
        return Password::verify($password, $this->data['hash']);
    }

    public function isLogged()
    {
        return Session::get('FORMWORK_USERNAME') === $this->data['username'];
    }

    public function isAdmin()
    {
        return $this->data['role'] === 'admin';
    }

    public function canDeleteUser(User $user)
    {
        return $this->isAdmin() && !$user->isLogged();
    }

    public function canChangeOptionsOf(User $user)
    {
        return $this->isAdmin() || $user->isLogged();
    }

    public function canChangePasswordOf(User $user)
    {
        return $this->isAdmin() || $user->isLogged();
    }

    public function canChangeRoleOf(User $user)
    {
        return $this->isAdmin() && !$user->isLogged();
    }

    public function lastAccess()
    {
        if (!is_null($this->lastAccess)) {
            return $this->lastAccess;
        }
        $lastAccess = Admin::instance()->registry('lastAccess')->get($this->data['username']);
        return $this->lastAccess = $lastAccess;
    }

    public function __call($name, $arguments)
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
