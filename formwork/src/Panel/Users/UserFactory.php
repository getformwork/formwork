<?php

namespace Formwork\Panel\Users;

use Formwork\Services\Container;

class UserFactory
{
    public function __construct(protected Container $container)
    {

    }

    public function make(array $data, array $permissions)
    {
        return $this->container->build(User::class, compact('data', 'permissions'));
    }
}
