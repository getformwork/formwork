<?php

namespace Formwork\Controllers;

use Formwork\Utils\Str;

abstract class AbstractController
{
    /**
     * Controller name
     *
     * @var string
     */
    protected $name;

    public function __construct()
    {
        $this->name = strtolower(Str::beforeLast(Str::afterLast(static::class, '\\'), 'Controller'));
    }
}
