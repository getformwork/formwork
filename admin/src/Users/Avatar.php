<?php

namespace Formwork\Admin\Users;

use Formwork\Admin\Admin;
use Formwork\Utils\FileSystem;

class Avatar
{
    /**
     * Default avatar URI
     *
     * @var string
     */
    protected const DEFAULT_AVATAR_URI = '/assets/images/avatar.png';

    /**
     * Avatar URI
     *
     * @var string
     */
    protected $uri;

    /**
     * Avatar file path
     *
     * @var string|null
     */
    protected $path;

    /**
     * Create a new Avatar instance
     */
    public function __construct(?string $filename)
    {
        $path = ADMIN_PATH . 'avatars/' . $filename;
        if ($filename !== null && FileSystem::exists($path)) {
            $this->uri = Admin::instance()->realUri('/avatars/' . basename($path));
            $this->path = $path;
        } else {
            $this->uri = Admin::instance()->realUri(self::DEFAULT_AVATAR_URI);
        }
    }

    /**
     * Return avatar URI
     *
     * @return string
     */
    public function uri()
    {
        return $this->uri;
    }

    /**
     * Return avatar path
     *
     * @return string|null
     */
    public function path()
    {
        return $this->path;
    }
}
