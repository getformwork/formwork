<?php

namespace Formwork\Users;

use Formwork\Services\Container;

final class UserFactory
{
    public function __construct(
        private Container $container,
    ) {}

    /**
     * Create a new User instance
     *
     * @param array<string, mixed> $data
     */
    public function make(array $data): User
    {
        return $this->container->build(User::class, compact('data'));
    }
}
