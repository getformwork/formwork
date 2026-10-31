<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Formwork\Cache\ArrayCache;
use Formwork\Cache\CacheManager;
use Formwork\Cache\FilesCache;
use Formwork\Config\Config;
use Formwork\Services\Container;
use Formwork\Services\Loaders\CacheServiceLoader;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesTempDirectory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(CacheServiceLoader::class)]
#[UsesTempDirectory]
final class CacheServiceLoaderTest extends TestCase
{
    public function testEveryConfiguredCacheIsAddedToTheManager(): void
    {
        $cacheManager = $this->manager([
            'pages' => $this->filesOptions(TESTS_TMP_PATH . '/pages'),
            'other' => $this->arrayOptions(),
        ]);

        $this->assertSame(['pages', 'other'], array_keys($cacheManager->getAll()));
    }

    public function testFilesCachesUseTheGivenPath(): void
    {
        $cache = $this->manager(['pages' => $this->filesOptions(TESTS_TMP_PATH . '/pages')])->get('pages');

        $this->assertInstanceOf(FilesCache::class, $cache);
        $this->assertSame(TESTS_TMP_PATH . '/pages', $cache->path());
    }

    #[DataProvider('requiredOptionProvider')]
    public function testRequiredOptionsMustBeDefined(string $option): void
    {
        $options = $this->filesOptions(TESTS_TMP_PATH . '/cache');
        unset($options[$option]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Cache "plugin" requires a "%s" option', $option));

        $this->manager(['plugin' => $options]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function requiredOptionProvider(): iterable
    {
        foreach (['enabled', 'handler', 'ttl', 'path'] as $option) {
            yield $option => [$option];
        }
    }

    public function testPathIsNotRequiredByOtherHandlers(): void
    {
        $this->assertInstanceOf(ArrayCache::class, $this->manager(['plugin' => $this->arrayOptions()])->get('plugin'));
    }

    public function testEmptyPathsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "path" option for cache "pages": expected non-empty string, string given');

        $this->manager(['pages' => $this->filesOptions('')]);
    }

    public function testCachesCannotShareThePath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache "second" uses the same path of cache "first"');

        $this->manager([
            'first'  => $this->filesOptions(TESTS_TMP_PATH . '/shared'),
            'second' => $this->filesOptions(TESTS_TMP_PATH . '/shared/'),
        ]);
    }

    public function testUnknownHandlersAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache handler "redis" is not supported');

        $this->manager(['pages' => $this->arrayOptions(['handler' => 'redis'])]);
    }

    public function testHandlerNamesAreMatchedExactly(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->manager(['pages' => $this->arrayOptions(['handler' => 'ARRAY'])]);
    }

    public function testDisabledCachesAreStillBuilt(): void
    {
        // Whether a cache is enabled is checked by the code using it
        $cache = $this->manager(['pages' => $this->arrayOptions(['enabled' => false, 'ttl' => 60])])->get('pages');

        $this->assertInstanceOf(ArrayCache::class, $cache);
        $this->assertSame(60, $cache->defaultTtl());
    }

    public function testNonBooleanEnabledOptionsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "enabled" option for cache "pages": expected boolean, int given');

        $this->manager(['pages' => $this->arrayOptions(['enabled' => 1])]);
    }

    public function testTimeToLiveIsReadFromEachCache(): void
    {
        $cacheManager = $this->manager(['first' => $this->arrayOptions(['ttl' => 123]), 'second' => $this->arrayOptions()]);

        $cache = $cacheManager->get('first');
        $second = $cacheManager->get('second');

        $this->assertInstanceOf(ArrayCache::class, $cache);
        $this->assertInstanceOf(ArrayCache::class, $second);
        $this->assertSame(123, $cache->defaultTtl());
        $this->assertNull($second->defaultTtl());
    }

    public function testNonIntegerTimeToLiveIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "ttl" option for cache "pages": expected integer or null, string given');

        $this->manager(['pages' => $this->arrayOptions(['ttl' => '60'])]);
    }

    public function testNonArrayCacheOptionsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid options for cache "pages": expected array, string given');

        $this->manager(['pages' => 'files']);
    }

    public function testInvalidCacheNamesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->manager(['Invalid' => $this->arrayOptions()]);
    }

    /**
     * @param array<string, mixed> $cache
     */
    private function manager(array $cache): CacheManager
    {
        $container = new Container();
        $container->define(Container::class, $container);

        $config = new Config(['system' => ['cache' => $cache]], resolved: true);

        return (new CacheServiceLoader($config))->load($container);
    }

    /**
     * Get complete options of a files cache
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function filesOptions(string $path, array $options = []): array
    {
        return [...['enabled' => true, 'handler' => 'files', 'path' => $path, 'ttl' => null], ...$options];
    }

    /**
     * Get complete options of an array cache
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function arrayOptions(array $options = []): array
    {
        return [...['enabled' => true, 'handler' => 'array', 'ttl' => null], ...$options];
    }
}
