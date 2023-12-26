<?php

namespace Formwork\Panel\Users;

class UserImage
{
    /**
     * Image URI
     */
    protected string $uri;

    /**
     * Image file path
     */
    protected string $path;

    public function __construct(string $path, string $uri)
    {
        $this->path = $path;
        $this->uri = $uri;
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
