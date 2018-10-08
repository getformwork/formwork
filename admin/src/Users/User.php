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

    protected $lastAccess;

    public function __construct($data)
    {
        $this->data = $data;
        foreach (array('username', 'fullname', 'hash', 'email', 'language', 'avatar') as $key) {
            $this->$key = $data[$key];
        }
        $this->avatar = new UserAvatar($this->avatar);
    }

    public function authenticate($password)
    {
        return Password::verify($password, $this->hash);
    }

    public function isLogged()
    {
        return Session::get('FORMWORK_USERNAME') === $this->username;
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
