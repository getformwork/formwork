<?php

namespace Formwork\Admin\Users;

use Formwork\Admin\Admin;
use Formwork\Data\AssociativeCollection;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;

class Users extends AssociativeCollection
{
    /**
     * All available roles
     *
     * @var array
     */
    protected static $roles = [];

    /**
     * Load all users and roles
     *
     * @return self
     */
    public static function load()
    {
        static::$roles = YAML::parseFile(Admin::SCHEMES_PATH . 'roles.yml');
        $users = [];
        foreach (FileSystem::listFiles(Admin::ACCOUNTS_PATH) as $file) {
            $parsedData = YAML::parseFile(Admin::ACCOUNTS_PATH . $file);
            $users[$parsedData['username']] = new User($parsedData);
        }
        return new static($users);
    }

    /**
     * Get all available roles
     *
     * @return array
     */
    public static function availableRoles()
    {
        $roles = [];
        foreach (static::$roles as $role => $data) {
            $roles[$role] = $data['title'];
        }
        return $roles;
    }

    /**
     * Get permissions for a given role
     *
     * @return array
     */
    public static function getRolePermissions(string $role)
    {
        return (array) static::$roles[$role]['permissions'];
    }
}
