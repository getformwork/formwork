<?php

namespace Formwork\Panel\Users;

use Formwork\Services\Container;

class UserFactory
{
    public function __construct(protected Container $container)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function make(array $data, Permissions $permissions): User
    {
        return $this->container->build(User::class, compact('data', 'permissions'));
    }
}
