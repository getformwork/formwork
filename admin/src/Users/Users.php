<?php

namespace Formwork\Admin\Users;

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
    protected static $roles = array();

    /**
     * Load all users and roles
     *
     * @return self
     */
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

    /**
     * Get all available roles
     *
     * @return array
     */
    public static function availableRoles()
    {
        $roles = array();
        foreach (static::$roles as $role => $data) {
            $roles[$role] = $data['title'];
        }
        return $roles;
    }

    /**
     * Get permissions for a given role
     *
     * @param string $role
     *
     * @return array
     */
    public static function getRolePermissions($role)
    {
        return (array) static::$roles[$role]['permissions'];
    }
}
