<?php

namespace Formwork\Panel\Users;

class UserImage
{
    public function __construct(protected string $path, protected string $uri)
    {
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
    public function path(): string
    {
        return $this->path;
    }
}
