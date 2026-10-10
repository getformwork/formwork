<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Formwork\Authentication\Authenticator;
use Formwork\Authentication\RateLimiter;
use Formwork\Config\Config;
use Formwork\Http\Request;
use Formwork\Http\Session\Session;
use Formwork\Log\Registry;
use Formwork\Services\Container;
use Formwork\Services\Loaders\AuthenticationServiceLoader;
use Formwork\Tests\TestCase;
use Formwork\Users\RoleCollection;
use Formwork\Users\Users;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(AuthenticationServiceLoader::class)]
final class AuthenticationServiceLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testAuthenticatorIsBuiltWithTheConfiguredRateLimiter(): void
    {
        $container = $this->container(maxAttempts: 7, resetTime: 321);

        $authenticator = (new AuthenticationServiceLoader($container->get(Config::class)))->load($container);

        $this->assertInstanceOf(Authenticator::class, $authenticator);
        $rateLimiter = $container->get(RateLimiter::class);
        $this->assertSame(7, (new ReflectionProperty($rateLimiter, 'limit'))->getValue($rateLimiter));
        $this->assertSame(321, (new ReflectionProperty($rateLimiter, 'resetTime'))->getValue($rateLimiter));
    }

    public function testRegistryIsStoredInTheConfiguredPath(): void
    {
        $container = $this->container(maxAttempts: 3, resetTime: 60);
        (new AuthenticationServiceLoader($container->get(Config::class)))->load($container);

        $registry = (new ReflectionProperty(RateLimiter::class, 'registry'))->getValue($container->get(RateLimiter::class));

        $this->assertInstanceOf(Registry::class, $registry);
        $this->assertSame(TESTS_TMP_PATH . '/registry/accessAttempts.json', (new ReflectionProperty($registry, 'filename'))->getValue($registry));
    }

    public function testEachLoadUsesTheSameRateLimiterOfTheContainer(): void
    {
        $container = $this->container(maxAttempts: 3, resetTime: 60);
        $loader = new AuthenticationServiceLoader($container->get(Config::class));

        $loader->load($container);

        $this->assertSame($container->get(RateLimiter::class), $container->get(RateLimiter::class));
    }

    private function container(int $maxAttempts, int $resetTime): Container
    {
        $config = new Config(['system' => ['authentication' => [
            'registryPath' => TESTS_TMP_PATH . '/registry',
            'limits'       => ['maxAttempts' => $maxAttempts, 'resetTime' => $resetTime],
        ]]], resolved: true);

        $request = new Request([], [], [], [], ['REMOTE_ADDR' => '203.0.113.7', 'REQUEST_METHOD' => 'GET']);

        $container = new Container();
        $container->define(Container::class, $container);
        $container->define(Config::class, $config);
        $container->define(Request::class, $request);
        $container->define(Session::class, $request->session());
        $container->define(Users::class, new Users([], new RoleCollection()));

        return $container;
    }
}
