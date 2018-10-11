<?php

namespace Formwork\Admin\Users;

use Formwork\Data\Collection;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;

class Users extends Collection
{
    protected static $roles;

    public function has($user)
    {
        return isset($this->items[$user]);
    }

    public function get($user)
    {
        if ($this->has($user)) {
            return $this->items[$user];
        }
    }

    public static function load()
    {
        static::$roles = YAML::parseFile(SCHEMES_PATH . 'roles.yml');
        $users = array();
        foreach (FileSystem::listFiles(ACCOUNTS_PATH) as $file) {
            $parsedData = YAML::parseFile(ACCOUNTS_PATH . $file);
            $users[$parsedData['username']] = new User($parsedData);
        }
        return new static($users);
    }

    public static function availableRoles()
    {
        $roles = array();
        foreach (static::$roles as $role => $data) {
            $roles[$role] = $data['title'] . ' (' . $role . ')';
        }
        return $roles;
    }

    public static function getRolePermissions($role)
    {
        return static::$roles[$role]['permissions'];
    }
}
