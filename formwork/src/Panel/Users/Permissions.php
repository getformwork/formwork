<?php

namespace Formwork\Panel\Users;

use Formwork\Utils\Str;

class Permissions
{
    /**
     * The permission values
     */
    protected array $permissions = [
        'dashboard' => false,
        'cache'     => false,
        'backup'    => false,
        'pages'     => false,
        'options'   => false,
        'updates'   => false,
        'users'     => false,
    ];

    /**
     * Create a new Permissions instance
     *
     * @param string $name Name of the role
     */
    public function __construct(string $name)
    {
        $this->permissions = array_merge($this->permissions, UserCollection::getRolePermissions($name));
    }

    /**
     * Return whether a permission is granted
     */
    public function has(string $permission): bool
    {
        if (array_key_exists($permission, $this->permissions)) {
            return (bool) $this->permissions[$permission];
        }

        // If $permission is not found try with the upper level one (super permission),
        // e.g. try with 'options' if 'options.updates' is not found

        $superPermission = Str::before($permission, '.');

        if ($superPermission !== $permission) {
            return $this->has($superPermission);
        }

        return false;
    }
}
