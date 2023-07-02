<?php

namespace Formwork\Panel\Users;

use Formwork\Data\AbstractCollection;
use Formwork\Formwork;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;

class UserCollection extends AbstractCollection
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
        foreach (FileSystem::listFiles($path = Formwork::instance()->config()->get('panel.paths.roles')) as $file) {
            $parsedData = YAML::parseFile($path . $file);
            $role = FileSystem::name($file);
            static::$roles[$role] = $parsedData;
        }
        $users = [];
        foreach (FileSystem::listFiles($path = Formwork::instance()->config()->get('panel.paths.accounts')) as $file) {
            $parsedData = YAML::parseFile($path . $file);
            $users[$parsedData['username']] = new User($parsedData);
        }
        return new static($users);
    }

    /**
     * Get all available roles
     */
    public function availableRoles(): array
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
