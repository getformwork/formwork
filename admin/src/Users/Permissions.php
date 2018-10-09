<?php

namespace Formwork\Admin\Users;

class Permissions
{
    protected $permissions = array(
        'dashboard' => false,
        'cache'     => false,
        'backup'    => false,
        'pages'     => false,
        'options'   => false,
        'users'     => false
    );

    public function __construct($name)
    {
        $this->permissions = array_merge($this->permissions, (array) Users::getRolePermissions($name));
    }

    public function has($permission)
    {
        if (array_key_exists($permission, $this->permissions)) {
            return (bool) $this->permissions[$permission];
        }

        $superPermission = strstr($permission, '.', true);

        if ($superPermission !== false) {
            return $this->has($superPermission);
        }

        return false;
    }
}
