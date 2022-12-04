<?php

namespace Formwork\Panel\Users;

use Formwork\Formwork;
use Formwork\Utils\FileSystem;

class Avatar
{
    /**
     * Default avatar URI
     */
    protected const DEFAULT_AVATAR_URI = '/assets/images/avatar.svg';

    /**
     * Avatar URI
     */
    protected string $uri;

    /**
     * Avatar file path
     */
    protected ?string $path = null;

    /**
     * Create a new Avatar instance
     */
    public function __construct(?string $filename)
    {
        $path = PANEL_PATH . 'avatars/' . $filename;
        if ($filename !== null && FileSystem::exists($path)) {
            $this->uri = Formwork::instance()->panel()->realUri('/avatars/' . basename($path));
            $this->path = $path;
        } else {
            $this->uri = Formwork::instance()->panel()->realUri(self::DEFAULT_AVATAR_URI);
        }
    }

    /**
     * Return avatar URI
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Return avatar path
     */
    public function path(): ?string
    {
        return $this->path;
    }
}
