<?php

namespace Formwork\Services\Loaders;

use Formwork\Authentication\Authenticator;
use Formwork\Authentication\RateLimiter;
use Formwork\Config\Config;
use Formwork\Log\Registry;
use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;
use Formwork\Utils\FileSystem;

/**
 * @since 2.3.0
 */
class AuthenticationServiceLoader implements ServiceLoaderInterface
{
    public function __construct(
        private Config $config,
    ) {}

    public function load(Container $container): Authenticator
    {
        $container->define(RateLimiter::class)
            ->parameter('registry', new Registry(FileSystem::joinPaths($this->config->get('system.authentication.registryPath'), 'accessAttempts.json')))
            ->parameter('limit', $this->config->get('system.authentication.limits.maxAttempts'))
            ->parameter('resetTime', $this->config->get('system.authentication.limits.resetTime'));

        return $container->build(Authenticator::class);
    }
}
