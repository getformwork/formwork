<?php

namespace Formwork\Admin\Users;

class Permissions
{
    /**
     * The permission values
     *
     * @var array
     */
    protected $permissions = array(
        'dashboard' => false,
        'cache'     => false,
        'backup'    => false,
        'pages'     => false,
        'options'   => false,
        'updates'   => false,
        'users'     => false
    );

    /**
     * Create a new Permissions instance
     *
     * @param string $name Name of the role
     */
    public function __construct($name)
    {
        $this->permissions = array_merge($this->permissions, Users::getRolePermissions($name));
    }

    /**
     * Return whether a permission is granted
     *
     * @param string $permission
     *
     * @return bool
     */
    public function has($permission)
    {
        if (array_key_exists($permission, $this->permissions)) {
            return (bool) $this->permissions[$permission];
        }

        // If $permission is not found try with the upper level one (super permission),
        // e.g. try with 'options' if 'options.updates' is not found

        $superPermission = strstr($permission, '.', true);

        if ($superPermission !== false) {
            return $this->has($superPermission);
        }

        return false;
    }
}
