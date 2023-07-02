<?php

namespace Formwork\Panel\Users;

use Formwork\Formwork;
use Formwork\Utils\FileSystem;

class UserImage
{
    /**
     * Default image URI
     */
    protected const DEFAULT_IMAGE_URI = '/assets/images/user-image.svg';

    /**
     * Image URI
     */
    protected string $uri;

    /**
     * Image file path
     */
    protected ?string $path = null;

    public function __construct(?string $filename)
    {
        $path = PANEL_PATH . 'assets' . DS . 'images' . DS . 'users' . DS . $filename;
        if ($filename !== null && FileSystem::exists($path)) {
            $this->uri = Formwork::instance()->panel()->realUri('/assets/images/users/' . basename($path));
            $this->path = $path;
        } else {
            $this->uri = Formwork::instance()->panel()->realUri(self::DEFAULT_IMAGE_URI);
        }
    }

    /**
     * Return image URI
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Return image path
     */
    public function path(): ?string
    {
        return $this->path;
    }
}
