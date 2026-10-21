<?php

namespace Formwork\Tests\Unit\Cache;

use DateInterval;
use DateTimeImmutable;
use Formwork\Cache\AbstractCache;
use Formwork\Cache\CacheItem;
use Formwork\Cache\FilesCache;
use Formwork\Parsers\Php;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use UnexpectedValueException;

#[CoversClass(FilesCache::class)]
#[CoversClass(AbstractCache::class)]
#[UsesTempDirectory]
final class FilesCacheTest extends CacheTestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = FileSystem::joinPaths(TESTS_TMP_PATH, 'cache');
    }

    public function testPathIsUsedAsIs(): void
    {
        $this->assertSame($this->path, $this->createCache()->path());
    }

    public function testDirectoryIsNotCreatedUntilSomethingIsStored(): void
    {
        $filesCache = $this->createCache();

        $filesCache->get('key');
        $filesCache->has('key');
        $filesCache->delete('key');
        $filesCache->clear();

        $this->assertDirectoryDoesNotExist($filesCache->path());
        $this->assertSame(0, $filesCache->count());
    }

    public function testDirectoryIsCreatedRecursivelyOnTheFirstSet(): void
    {
        $filesCache = new FilesCache(FileSystem::joinPaths($this->path, 'deeply', 'nested'));

        $filesCache->set('key', 'value');

        $this->assertDirectoryExists($filesCache->path());
    }

    public function testItemsArePersistedAcrossInstances(): void
    {
        $this->createCache()->set('key', ['persisted' => true]);

        $this->assertSame(['persisted' => true], $this->createCache()->get('key'));
        $this->assertSame(1, $this->createCache()->count());
    }

    public function testCachesWithDifferentPathsDoNotShareItems(): void
    {
        $first = new FilesCache(FileSystem::joinPaths($this->path, 'first'));
        $second = new FilesCache(FileSystem::joinPaths($this->path, 'second'));
        $first->set('key', 1);
        $second->set('key', 2);

        $first->clear();

        $this->assertFalse($first->has('key'));
        $this->assertSame(2, $second->get('key'));
    }

    public function testFilesAreNamedAfterTheHashOfTheKey(): void
    {
        $filesCache = $this->createCache();

        $filesCache->set('some key', 'value');

        $this->assertSame([hash('sha256', 'some key')], iterator_to_array(FileSystem::listFiles($filesCache->path()), false));
    }

    public function testKeysCannotInfluenceTheFilePath(): void
    {
        $filesCache = $this->createCache();

        // Keys containing path separators are rejected, others are hashed
        $filesCache->set('..', 'dots');
        $filesCache->set('.hidden', 'hidden');

        $files = iterator_to_array(FileSystem::listFiles($filesCache->path()), false);

        $this->assertCount(2, $files);
        foreach ($files as $file) {
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $file);
        }
        $this->assertFileDoesNotExist(FileSystem::joinPaths($this->path, 'test.php'));
    }

    public function testStoredFilesAreWrittenAtomically(): void
    {
        $filesCache = $this->createCache();
        $filesCache->set('key', 'old');

        $filesCache->set('key', 'new');

        // Only the cache item remains, without temporary files
        $this->assertCount(1, iterator_to_array(FileSystem::listFiles($filesCache->path()), false));
    }

    public function testFilesContainTheCacheItem(): void
    {
        $filesCache = $this->createCache();
        $filesCache->set('key', 'value', 60);

        $item = Php::parseFile(FileSystem::joinPaths($filesCache->path(), hash('sha256', 'key')));

        $this->assertInstanceOf(CacheItem::class, $item);
        $this->assertSame('value', $item->value());
        $this->assertSame($item->cachedTime() + 60, $item->expirationTime());
    }

    public function testValuesThatCannotBeEncodedAreRejectedWithoutStoringAnything(): void
    {
        $filesCache = $this->createCache();

        $this->captureException(UnexpectedValueException::class, static fn() => $filesCache->set('closure', static fn() => null));

        $this->assertFalse($filesCache->has('closure'));
        $this->assertSame(0, $filesCache->count());
    }

    public function testFailedSetKeepsThePreviousValue(): void
    {
        $filesCache = $this->createCache();
        $filesCache->set('key', 'previous');

        $this->captureException(UnexpectedValueException::class, static fn() => $filesCache->set('key', static fn() => null));

        $this->assertSame('previous', $filesCache->get('key'));
    }

    #[DataProvider('invalidFileContentProvider')]
    public function testFilesWithoutAValidCacheItemAreReportedAsUnexpectedValues(string $content): void
    {
        $filesCache = $this->createCache();
        $filesCache->set('key', 'value');
        FileSystem::write($this->fileOf($filesCache, 'key'), $content);

        $this->expectException(UnexpectedValueException::class);
        $filesCache->get('key');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidFileContentProvider(): iterable
    {
        yield 'scalar' => ["<?php\n\nreturn 'not a cache item';\n"];
        yield 'array' => ["<?php\n\nreturn ['value' => 1];\n"];
        yield 'empty file' => [''];
        yield 'truncated file' => ["<?php\n\nreturn [\n"];
    }

    public function testExpiredFilesAreDeletedWhenAccessed(): void
    {
        $filesCache = $this->createCache();
        $filesCache->set('key', 'value', 100);
        $this->expire($filesCache, 'key');

        $this->assertNull($filesCache->get('key'));

        $this->assertFileDoesNotExist($this->fileOf($filesCache, 'key'));
    }

    /**
     * @param non-empty-string $first
     * @param non-empty-string $second
     */
    #[DataProvider('hashCollisionLikeKeyProvider')]
    public function testSimilarKeysUseDifferentFiles(string $first, string $second): void
    {
        $filesCache = $this->createCache();
        $filesCache->set($first, 'first');
        $filesCache->set($second, 'second');

        $this->assertSame('first', $filesCache->get($first));
        $this->assertSame('second', $filesCache->get($second));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function hashCollisionLikeKeyProvider(): iterable
    {
        yield 'case' => ['key', 'KEY'];
        yield 'whitespace' => ['key', 'key '];
        yield 'prefix' => ['key', 'key1'];
        yield 'numeric strings' => ['1', '01'];
        yield 'unicode normalization' => ["e\u{301}", "\u{e9}"];
    }

    public function testValuesWithObjectsOfOtherClassesRoundTrip(): void
    {
        $filesCache = $this->createCache();
        $value = ['object' => (object) ['nested' => [1, 2, 3]], 'date' => new DateTimeImmutable('2025-01-02 03:04:05')];

        $filesCache->set('key', $value);

        $this->assertEquals($value, $filesCache->get('key'));
    }

    public function testCountIgnoresNothingInTheDirectory(): void
    {
        $filesCache = $this->createCache();
        $filesCache->setMultiple(['a' => 1, 'b' => 2]);

        $this->assertSame(2, $filesCache->count());

        $filesCache->delete('a');

        $this->assertSame(1, $filesCache->count());
    }

    public function testStdClassValuesRoundTrip(): void
    {
        $filesCache = $this->createCache();
        $filesCache->set('key', new stdClass());

        $this->assertInstanceOf(stdClass::class, $filesCache->get('key'));
    }

    protected function createCache(int|DateInterval|null $defaultTtl = null): FilesCache
    {
        return new FilesCache($this->path, $defaultTtl);
    }

    /**
     * @param non-empty-string $key
     */
    protected function expire(AbstractCache $cache, string $key): void
    {
        $item = $cache->getItem($key);

        Php::encodeToFile(
            new CacheItem($item?->value(), time() - 10, ($item?->cachedTime() ?? time()) - 100),
            $this->fileOf($cache, $key),
        );
    }

    private function fileOf(AbstractCache $cache, string $key): string
    {
        $this->assertInstanceOf(FilesCache::class, $cache);

        return FileSystem::joinPaths($cache->path(), hash('sha256', $key));
    }
}
