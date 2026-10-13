<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Formwork\Cache\ArrayCache;
use Formwork\Cache\CacheManager;
use Formwork\Cache\Exceptions\InvalidNamespaceException;
use Formwork\Cache\FilesCache;
use Formwork\Config\Config;
use Formwork\Services\Container;
use Formwork\Services\Loaders\CacheServiceLoader;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CacheServiceLoader::class)]
final class CacheServiceLoaderTest extends TestCase
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

    public function testFilesCacheIsTheDefaultHandler(): void
    {
        $cache = $this->loader('pages')->load($this->container());

        $this->assertInstanceOf(FilesCache::class, $cache);
        $this->assertSame('pages', $cache->namespace());
    }

    public function testHandlerCanBeChosenInTheConfiguration(): void
    {
        $cache = $this->loader('pages', config: ['cache' => ['namespaces' => ['pages' => ['handler' => 'array']]]])->load($this->container());

        $this->assertInstanceOf(ArrayCache::class, $cache);
    }

    public function testHandlerParameterWinsOverTheConfiguration(): void
    {
        $cache = $this->loader('pages', handler: 'array', config: ['cache' => ['namespaces' => ['pages' => ['handler' => 'files']]]])->load($this->container());

        $this->assertInstanceOf(ArrayCache::class, $cache);
    }

    public function testConfigurationOfOtherNamespacesIsIgnored(): void
    {
        $cache = $this->loader('pages', config: ['cache' => ['namespaces' => ['other' => ['handler' => 'array']]]])->load($this->container());

        $this->assertInstanceOf(FilesCache::class, $cache);
    }

    public function testUnknownHandlersAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache handler "redis" is not supported');

        $this->loader('pages', handler: 'redis')->load($this->container());
    }

    public function testHandlerNamesAreMatchedExactly(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->loader('pages', handler: 'ARRAY')->load($this->container());
    }

    public function testDefaultTimeToLiveComesFromTheConfiguration(): void
    {
        $cache = $this->loader('pages', handler: 'array', config: ['cache' => ['time' => 123]])->load($this->container());

        $this->assertSame(123, $cache->defaultTtl());
    }

    public function testTimeToLiveParameterWinsOverTheConfiguration(): void
    {
        $cache = $this->loader('pages', handler: 'array', defaultTtl: 5)->load($this->container());

        $this->assertSame(5, $cache->defaultTtl());
    }

    public function testCachePathComesFromTheConfigurationOrTheParameter(): void
    {
        $fromConfig = $this->loader('pages', config: ['cache' => ['path' => TESTS_TMP_PATH . '/from-config']])->load($this->container());
        $fromParameter = $this->loader('pages', path: TESTS_TMP_PATH . '/from-parameter')->load($this->container());

        $fromConfig->set('key', 'value');
        $fromParameter->set('key', 'value');

        $this->assertDirectoryExists(TESTS_TMP_PATH . '/from-config');
        $this->assertDirectoryExists(TESTS_TMP_PATH . '/from-parameter');
    }

    public function testLoadedCachesAreRegisteredInTheManager(): void
    {
        $manager = new CacheManager();
        $cache = $this->loader('pages', handler: 'array', manager: $manager)->load($this->container());

        $this->assertTrue($manager->has('pages'));
        $this->assertSame($cache, $manager->get('pages'));
    }

    public function testInvalidNamespacesAreRejected(): void
    {
        $this->expectException(InvalidNamespaceException::class);

        $this->loader('invalid namespace!', handler: 'array')->load($this->container());
    }

    private function container(): Container
    {
        $container = new Container();
        $container->define(Container::class, $container);
        return $container;
    }

    /**
     * @param non-empty-string     $namespace
     * @param array<string, mixed> $config
     */
    private function loader(string $namespace, ?string $path = null, ?int $defaultTtl = null, ?string $handler = null, array $config = [], ?CacheManager $manager = null): CacheServiceLoader
    {
        $config = array_replace_recursive(['cache' => ['path' => TESTS_TMP_PATH . '/cache', 'time' => 900]], $config);

        return new CacheServiceLoader(new Config(['system' => $config], resolved: true), $manager ?? new CacheManager(), $namespace, $path, $defaultTtl, $handler);
    }
}
