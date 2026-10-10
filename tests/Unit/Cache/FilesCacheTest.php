<?php

namespace Formwork\Tests\Unit\Cache;

use DateInterval;
use Formwork\Cache\AbstractCache;
use Formwork\Cache\CacheItem;
use Formwork\Cache\FilesCache;
use Formwork\Parsers\Php;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use UnexpectedValueException;

#[CoversClass(FilesCache::class)]
#[CoversClass(AbstractCache::class)]
final class FilesCacheTest extends CacheTestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->path = FileSystem::joinPaths(TESTS_TMP_PATH, 'cache');
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testPathIncludesTheNamespace(): void
    {
        $this->assertSame(FileSystem::joinPaths($this->path, 'pages'), $this->createCache('pages')->path());
    }

    public function testDirectoryIsNotCreatedUntilSomethingIsStored(): void
    {
        $cache = $this->createCache();

        $cache->get('key');
        $cache->has('key');
        $cache->delete('key');
        $cache->clear();

        $this->assertDirectoryDoesNotExist($cache->path());
        $this->assertSame(0, $cache->count());
    }

    public function testDirectoryIsCreatedRecursivelyOnTheFirstSet(): void
    {
        $cache = new FilesCache(FileSystem::joinPaths($this->path, 'deeply', 'nested'), 'test');

        $cache->set('key', 'value');

        $this->assertDirectoryExists($cache->path());
    }

    public function testItemsArePersistedAcrossInstances(): void
    {
        $this->createCache()->set('key', ['persisted' => true]);

        $this->assertSame(['persisted' => true], $this->createCache()->get('key'));
        $this->assertSame(1, $this->createCache()->count());
    }

    public function testNamespacesAreStoredInSeparateDirectories(): void
    {
        $first = $this->createCache('first');
        $second = $this->createCache('second');

        $first->set('key', 1);
        $second->set('key', 2);

        $this->assertNotSame($first->path(), $second->path());
        $this->assertDirectoryExists($first->path());
        $this->assertDirectoryExists($second->path());
    }

    public function testClearOnlyRemovesTheItemsOfTheNamespace(): void
    {
        $first = $this->createCache('first');
        $second = $this->createCache('second');
        $first->set('key', 1);
        $second->set('key', 2);

        $first->clear();

        $this->assertDirectoryExists($first->path());
        $this->assertSame(2, $second->get('key'));
    }

    public function testFilesAreNamedAfterTheHashOfTheKey(): void
    {
        $cache = $this->createCache();

        $cache->set('some key', 'value');

        $this->assertSame([hash('sha256', 'some key')], iterator_to_array(FileSystem::listFiles($cache->path()), false));
    }

    public function testKeysCannotInfluenceTheFilePath(): void
    {
        $cache = $this->createCache();

        // Keys containing path separators are rejected, others are hashed
        $cache->set('..', 'dots');
        $cache->set('.hidden', 'hidden');

        $files = iterator_to_array(FileSystem::listFiles($cache->path()), false);

        $this->assertCount(2, $files);
        foreach ($files as $file) {
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $file);
        }
        $this->assertFileDoesNotExist(FileSystem::joinPaths($this->path, 'test.php'));
    }

    public function testStoredFilesAreWrittenAtomically(): void
    {
        $cache = $this->createCache();
        $cache->set('key', 'old');

        $cache->set('key', 'new');

        // Only the cache item remains, without temporary files
        $this->assertCount(1, iterator_to_array(FileSystem::listFiles($cache->path()), false));
    }

    public function testFilesContainTheCacheItem(): void
    {
        $cache = $this->createCache();
        $cache->set('key', 'value', 60);

        $item = Php::parseFile(FileSystem::joinPaths($cache->path(), hash('sha256', 'key')));

        $this->assertInstanceOf(CacheItem::class, $item);
        $this->assertSame('value', $item->value());
        $this->assertSame($item->cachedTime() + 60, $item->expirationTime());
    }

    public function testValuesThatCannotBeEncodedAreRejectedWithoutStoringAnything(): void
    {
        $cache = $this->createCache();

        try {
            $cache->set('closure', static fn() => null);
            $this->fail('Closures cannot be cached.');
        } catch (UnexpectedValueException) {
        }

        $this->assertFalse($cache->has('closure'));
        $this->assertSame(0, $cache->count());
    }

    public function testFailedSetKeepsThePreviousValue(): void
    {
        $cache = $this->createCache();
        $cache->set('key', 'previous');

        try {
            $cache->set('key', static fn() => null);
        } catch (UnexpectedValueException) {
        }

        $this->assertSame('previous', $cache->get('key'));
    }

    #[DataProvider('invalidFileContentProvider')]
    public function testFilesWithoutAValidCacheItemAreReportedAsUnexpectedValues(string $content): void
    {
        $cache = $this->createCache();
        $cache->set('key', 'value');
        FileSystem::write($this->fileOf($cache, 'key'), $content);

        $this->expectException(UnexpectedValueException::class);
        $cache->get('key');
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
        $cache = $this->createCache();
        $cache->set('key', 'value', 100);
        $this->expire($cache, 'key');

        $this->assertNull($cache->get('key'));

        $this->assertFileDoesNotExist($this->fileOf($cache, 'key'));
    }

    #[DataProvider('hashCollisionLikeKeyProvider')]
    public function testSimilarKeysUseDifferentFiles(string $first, string $second): void
    {
        $cache = $this->createCache();
        $cache->set($first, 'first');
        $cache->set($second, 'second');

        $this->assertSame('first', $cache->get($first));
        $this->assertSame('second', $cache->get($second));
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
        $cache = $this->createCache();
        $value = ['object' => (object) ['nested' => [1, 2, 3]], 'date' => new \DateTimeImmutable('2025-01-02 03:04:05')];

        $cache->set('key', $value);

        $this->assertEquals($value, $cache->get('key'));
    }

    public function testCountIgnoresNothingInTheDirectory(): void
    {
        $cache = $this->createCache();
        $cache->setMultiple(['a' => 1, 'b' => 2]);

        $this->assertSame(2, $cache->count());

        $cache->delete('a');

        $this->assertSame(1, $cache->count());
    }

    public function testStdClassValuesRoundTrip(): void
    {
        $cache = $this->createCache();
        $cache->set('key', new stdClass());

        $this->assertInstanceOf(stdClass::class, $cache->get('key'));
    }

    protected function createCache(string $namespace = 'test', int|DateInterval|null $defaultTtl = null): FilesCache
    {
        return new FilesCache($this->path, $namespace, $defaultTtl);
    }

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
        return FileSystem::joinPaths($cache->path(), hash('sha256', $key));
    }
}
