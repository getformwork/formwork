<?php

namespace Formwork\Tests\Unit\Cache;

use Formwork\Cache\ArrayCache;
use Formwork\Cache\CacheManager;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CacheManager::class)]
final class CacheManagerTest extends TestCase
{
    public function testManagerStartsEmptyByDefault(): void
    {
        $cacheManager = new CacheManager();

        $this->assertSame([], $cacheManager->getAll());
        $this->assertFalse($cacheManager->has('pages'));
    }

    public function testCachesGivenToTheConstructorAreAvailable(): void
    {
        $arrayCache = new ArrayCache('pages');

        $cacheManager = new CacheManager(['pages' => $arrayCache]);

        $this->assertTrue($cacheManager->has('pages'));
        $this->assertSame($arrayCache, $cacheManager->get('pages'));
    }

    public function testCachesCanBeAddedByNamespace(): void
    {
        $cacheManager = new CacheManager();
        $arrayCache = new ArrayCache('pages');

        $cacheManager->add($arrayCache);

        $this->assertTrue($cacheManager->has('pages'));
        $this->assertSame($arrayCache, $cacheManager->get('pages'));
    }

    public function testUnknownCachesAreReported(): void
    {
        $cacheManager = new CacheManager(['pages' => new ArrayCache('pages')]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache "missing" does not exist');
        $cacheManager->get('missing');
    }

    public function testAddingACacheWithTheSameNamespaceReplacesThePreviousOne(): void
    {
        $cacheManager = new CacheManager();
        $first = new ArrayCache('pages');
        $second = new ArrayCache('pages');

        $cacheManager->add($first);
        $cacheManager->add($second);

        $this->assertSame($second, $cacheManager->get('pages'));
        $this->assertCount(1, $cacheManager->getAll());
    }

    public function testMultipleCachesCanBeRetrieved(): void
    {
        $pages = new ArrayCache('pages');
        $files = new ArrayCache('files');
        $cacheManager = new CacheManager();
        $cacheManager->add($pages);
        $cacheManager->add($files);

        $this->assertSame(['files' => $files, 'pages' => $pages], $cacheManager->getMultiple(['files', 'pages']));
        $this->assertSame([], $cacheManager->getMultiple([]));
    }

    public function testRetrievingMultipleCachesFailsWhenOneIsMissing(): void
    {
        $cacheManager = new CacheManager(['pages' => new ArrayCache('pages')]);

        $this->expectException(InvalidArgumentException::class);
        $cacheManager->getMultiple(['pages', 'missing']);
    }

    public function testAllCachesCanBeRetrievedByNamespace(): void
    {
        $pages = new ArrayCache('pages');
        $files = new ArrayCache('files');
        $cacheManager = new CacheManager();
        $cacheManager->add($pages);
        $cacheManager->add($files);

        $this->assertSame(['pages' => $pages, 'files' => $files], $cacheManager->getAll());
    }

    public function testCachesAreIndependent(): void
    {
        $cacheManager = new CacheManager();
        $cacheManager->add(new ArrayCache('pages'));
        $cacheManager->add(new ArrayCache('files'));

        $cacheManager->get('pages')->set('key', 'value');

        $this->assertFalse($cacheManager->get('files')->has('key'));
    }

    public function testNamespaceLookupIsCaseSensitive(): void
    {
        $cacheManager = new CacheManager(['pages' => new ArrayCache('pages')]);

        $this->assertFalse($cacheManager->has('Pages'));
    }
}
