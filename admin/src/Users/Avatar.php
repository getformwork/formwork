<?php

namespace Formwork\Admin\Users;

use Formwork\Admin\Admin;
use Formwork\Files\File;
use Formwork\Utils\FileSystem;

class Avatar extends File
{
    /**
     * Create a new Avatar instance
     *
     * @param string $filename
     */
    public function __construct($filename)
    {
        $path = ADMIN_PATH . 'avatars/' . $filename;
        if (!empty($filename) && FileSystem::exists($path)) {
            parent::__construct($path);
            $this->uri = Admin::instance()->realUri('/avatars/' . $this->name);
        } else {
            $this->uri = Admin::instance()->realUri('/assets/images/avatar.png');
        }
    }
}
