<?php

namespace Formwork\Users;

use Formwork\Translations\Translations;
use Formwork\Utils\Str;

class Role
{
    public function __construct(
        protected string $id,
        protected string $title,
        protected Permissions $permissions,
        protected Translations $translations,
    ) {}

    /**
     * Return role id
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Return role title
     */
    public function title(): string
    {
        return Str::interpolate($this->title, fn($key) => $this->translations->getCurrent()->translate($key));
    }

    /**
     * Return image path
     */
    public function permissions(): Permissions
    {
        return $this->permissions;
    }
}
