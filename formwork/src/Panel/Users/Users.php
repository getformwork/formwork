<?php

namespace Formwork\Panel\Users;

use Formwork\Data\AbstractCollection;
use Formwork\Formwork;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;

class Users extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = User::class;

    /**
     * All available roles
     */
    protected static array $roles = [];

    /**
     * Load all users and roles
     */
    public static function load(): self
    {
        static::$roles = Formwork::instance()->schemes()->get('panel', 'roles')->get('data');
        $users = [];
        foreach (FileSystem::listFiles(Formwork::instance()->config()->get('panel.paths.accounts')) as $file) {
            $parsedData = YAML::parseFile(Formwork::instance()->config()->get('panel.paths.accounts') . $file);
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
