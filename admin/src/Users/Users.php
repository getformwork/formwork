<?php

namespace Formwork\Admin\Users;

use Formwork\Data\Collection;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;

class Users extends Collection
{
    /**
     * All available roles
     *
     * @var array
     */
    protected static $roles;

    /**
     * Return whether a user is in the collection
     *
     * @param string $user
     *
     * @return bool
     */
    public function has($user)
    {
        return isset($this->items[$user]);
    }

    /**
     * Get a user by name
     *
     * @param string $user
     *
     * @return User
     */
    public function get($user)
    {
        if ($this->has($user)) {
            return $this->items[$user];
        }
    }

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
