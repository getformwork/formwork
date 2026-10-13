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
        $manager = new CacheManager();

        $this->assertSame([], $manager->getAll());
        $this->assertFalse($manager->has('pages'));
    }

    public function testCachesGivenToTheConstructorAreAvailable(): void
    {
        $pages = new ArrayCache('pages');

        $manager = new CacheManager(['pages' => $pages]);

        $this->assertTrue($manager->has('pages'));
        $this->assertSame($pages, $manager->get('pages'));
    }

    public function testCachesCanBeAddedByNamespace(): void
    {
        $manager = new CacheManager();
        $pages = new ArrayCache('pages');

        $manager->add($pages);

        $this->assertTrue($manager->has('pages'));
        $this->assertSame($pages, $manager->get('pages'));
    }

    public function testUnknownCachesAreReported(): void
    {
        $manager = new CacheManager(['pages' => new ArrayCache('pages')]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache "missing" does not exist');
        $manager->get('missing');
    }

    public function testAddingACacheWithTheSameNamespaceReplacesThePreviousOne(): void
    {
        $manager = new CacheManager();
        $first = new ArrayCache('pages');
        $second = new ArrayCache('pages');

        $manager->add($first);
        $manager->add($second);

        $this->assertSame($second, $manager->get('pages'));
        $this->assertCount(1, $manager->getAll());
    }

    public function testMultipleCachesCanBeRetrieved(): void
    {
        $pages = new ArrayCache('pages');
        $files = new ArrayCache('files');
        $manager = new CacheManager();
        $manager->add($pages);
        $manager->add($files);

        $this->assertSame(['files' => $files, 'pages' => $pages], $manager->getMultiple(['files', 'pages']));
        $this->assertSame([], $manager->getMultiple([]));
    }

    public function testRetrievingMultipleCachesFailsWhenOneIsMissing(): void
    {
        $manager = new CacheManager(['pages' => new ArrayCache('pages')]);

        $this->expectException(InvalidArgumentException::class);
        $manager->getMultiple(['pages', 'missing']);
    }

    public function testAllCachesCanBeRetrievedByNamespace(): void
    {
        $pages = new ArrayCache('pages');
        $files = new ArrayCache('files');
        $manager = new CacheManager();
        $manager->add($pages);
        $manager->add($files);

        $this->assertSame(['pages' => $pages, 'files' => $files], $manager->getAll());
    }

    public function testCachesAreIndependent(): void
    {
        $manager = new CacheManager();
        $manager->add(new ArrayCache('pages'));
        $manager->add(new ArrayCache('files'));

        $manager->get('pages')->set('key', 'value');

        $this->assertFalse($manager->get('files')->has('key'));
    }

    public function testNamespaceLookupIsCaseSensitive(): void
    {
        $manager = new CacheManager(['pages' => new ArrayCache('pages')]);

        $this->assertFalse($manager->has('Pages'));
    }
}
