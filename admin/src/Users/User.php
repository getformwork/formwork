<?php

namespace Formwork\Admin\Users;

use Formwork\Admin\Admin;
use Formwork\Admin\Security\Password;
use Formwork\Admin\Utils\Session;
use Formwork\Data\DataGetter;
use LogicException;

class User extends DataGetter
{
    protected $username;

    protected $fullname;

    protected $hash;

    protected $email;

    protected $language;

    protected $avatar;

    protected $role;

    protected $permissions;

    protected $lastAccess;

    public function __construct($data)
    {
        $this->data = $data;
        foreach (array('username', 'fullname', 'hash', 'email', 'language', 'avatar') as $key) {
            $this->$key = $data[$key];
        }
        $this->role = isset($data['role']) ? $data['role'] : 'user';
        $this->permissions = new Permissions($this->role);
        $this->avatar = new Avatar($this->avatar);
    }

    public function authenticate($password)
    {
        return Password::verify($password, $this->hash);
    }

    public function isLogged()
    {
        return Session::get('FORMWORK_USERNAME') === $this->username;
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
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
        $lastAccess = Admin::instance()->registry('lastAccess')->get($this->username);
        $this->lastAccess = $lastAccess;
        return $this->lastAccess;
    }

    public function __call($name, $arguments)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new LogicException('Invalid method ' . static::class . '::' . $name);
    }
}
