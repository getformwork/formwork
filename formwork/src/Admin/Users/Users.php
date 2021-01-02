<?php

namespace Formwork\Admin\Users;

use Formwork\Admin\Admin;
use Formwork\Data\AssociativeCollection;
use Formwork\Formwork;
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
     */
    public static function load(): self
    {
        static::$roles = Formwork::instance()->schemes()->get('admin', 'roles')->get('data');
        $users = [];
        foreach (FileSystem::listFiles(Admin::ACCOUNTS_PATH) as $file) {
            $parsedData = YAML::parseFile(Admin::ACCOUNTS_PATH . $file);
            $users[$parsedData['username']] = new User($parsedData);
        }
        return new static($users);
    }

    /**
     * Get all available roles
     */
    public static function availableRoles(): array
    {
        $roles = [];
        foreach (static::$roles as $role => $data) {
            $roles[$role] = $data['title'];
        }
        return $roles;
    }

    /**
     * Get permissions for a given role
     */
    public static function getRolePermissions(string $role): array
    {
        return (array) static::$roles[$role]['permissions'];
    }
}
