<?php

namespace Formwork\Admin\Users;

use Formwork\Admin\Admin;
use Formwork\Files\File;
use Formwork\Utils\FileSystem;

class UserAvatar extends File
{
    protected $uri;

    public function __construct($filename)
    {
        $path = ADMIN_PATH . 'avatars/' . $filename;
        if (!empty($filename) && FileSystem::exists($path)) {
            parent::__construct($path);
            $this->uri = Admin::instance()->uri('/avatars/' . $this->name);
        } else {
            $this->uri = Admin::instance()->uri('/assets/images/avatar.png');
        }
    }
}
