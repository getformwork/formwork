<?php

namespace Formwork\Tests\Unit\Cache;

use Formwork\Cache\ArrayCache;
use Formwork\Cache\CacheManager;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(CacheManager::class)]
final class CacheManagerTest extends TestCase
{
    public function testManagerStartsEmptyByDefault(): void
    {
        $cacheManager = new CacheManager();

        $this->assertSame([], $cacheManager->getAll());
        $this->assertFalse($cacheManager->has('pages'));
    }

    public function testCachesGivenToTheConstructorAreAvailableByName(): void
    {
        $arrayCache = new ArrayCache();

        $cacheManager = new CacheManager(['pages' => $arrayCache]);

        $this->assertTrue($cacheManager->has('pages'));
        $this->assertSame($arrayCache, $cacheManager->get('pages'));
    }

    public function testCachesCanBeAddedByName(): void
    {
        $cacheManager = new CacheManager();
        $arrayCache = new ArrayCache();

        $cacheManager->add('pages', $arrayCache);

        $this->assertTrue($cacheManager->has('pages'));
        $this->assertSame($arrayCache, $cacheManager->get('pages'));
    }

    public function testTheSameInstanceCanBeAddedWithDifferentNames(): void
    {
        $arrayCache = new ArrayCache();
        $cacheManager = new CacheManager(['first' => $arrayCache, 'second' => $arrayCache]);

        $this->assertSame($cacheManager->get('first'), $cacheManager->get('second'));
    }

    public function testUnknownCachesAreReported(): void
    {
        $cacheManager = new CacheManager(['pages' => new ArrayCache()]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache "missing" does not exist');
        $cacheManager->get('missing');
    }

    public function testAddingACacheWithTheSameNameReplacesThePreviousOne(): void
    {
        $cacheManager = new CacheManager();
        $first = new ArrayCache();
        $second = new ArrayCache();

        $cacheManager->add('pages', $first);
        $cacheManager->add('pages', $second);

        $this->assertSame($second, $cacheManager->get('pages'));
        $this->assertCount(1, $cacheManager->getAll());
    }

    public function testMultipleCachesCanBeRetrieved(): void
    {
        $pages = new ArrayCache();
        $files = new ArrayCache();
        $cacheManager = new CacheManager(['pages' => $pages, 'files' => $files]);

        $this->assertSame(['files' => $files, 'pages' => $pages], $cacheManager->getMultiple(['files', 'pages']));
        $this->assertSame([], $cacheManager->getMultiple([]));
    }

    public function testRetrievingMultipleCachesFailsWhenOneIsMissing(): void
    {
        $cacheManager = new CacheManager(['pages' => new ArrayCache()]);

        $this->expectException(InvalidArgumentException::class);
        $cacheManager->getMultiple(['pages', 'missing']);
    }

    public function testAllCachesCanBeRetrievedByName(): void
    {
        $pages = new ArrayCache();
        $files = new ArrayCache();
        $cacheManager = new CacheManager();
        $cacheManager->add('pages', $pages);
        $cacheManager->add('files', $files);

        $this->assertSame(['pages' => $pages, 'files' => $files], $cacheManager->getAll());
    }

    public function testNameLookupIsCaseSensitive(): void
    {
        $cacheManager = new CacheManager(['pages' => new ArrayCache()]);

        $this->assertFalse($cacheManager->has('Pages'));
    }

    #[DataProvider('invalidNameProvider')]
    public function testInvalidNamesAreRejected(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Cache name "%s" is not valid', $name));
        (new CacheManager())->add($name, new ArrayCache());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidNameProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'uppercase' => ['Pages'];
        yield 'dot' => ['my.cache'];
        yield 'underscore' => ['my_cache'];
        yield 'leading hyphen' => ['-cache'];
        yield 'space' => ['my cache'];
        yield 'slash' => ['a/b'];
    }

    #[DataProvider('reservedNameProvider')]
    public function testReservedNamesAreRejected(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Cache name "%s" is reserved', $name));
        (new CacheManager())->add($name, new ArrayCache());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function reservedNameProvider(): iterable
    {
        foreach (CacheManager::RESERVED_NAMES as $name) {
            yield $name => [$name];
        }
    }

    public function testNamesWithHyphensAndDigitsAreAccepted(): void
    {
        $cacheManager = new CacheManager(['my-plugin' => new ArrayCache(), '2fa' => new ArrayCache()]);

        $this->assertSame(['my-plugin', '2fa'], array_keys($cacheManager->getAll()));
    }
}
