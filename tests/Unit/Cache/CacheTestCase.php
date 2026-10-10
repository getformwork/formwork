<?php

namespace Formwork\Tests\Unit\Cache;

use ArrayObject;
use DateInterval;
use Formwork\Cache\AbstractCache;
use Formwork\Cache\CacheItem;
use Formwork\Cache\CountableCache;
use Formwork\Cache\Exceptions\InvalidKeyException;
use Formwork\Cache\Exceptions\InvalidNamespaceException;
use Formwork\Cache\NamespacedCacheInterface;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;
use stdClass;

/**
 * Behavior shared by every cache implementation
 */
abstract class CacheTestCase extends TestCase
{
    public function testCachesImplementThePsrInterfaces(): void
    {
        $cache = $this->createCache();

        $this->assertInstanceOf(CacheInterface::class, $cache);
        $this->assertInstanceOf(NamespacedCacheInterface::class, $cache);
        $this->assertInstanceOf(CountableCache::class, $cache);
    }

    public function testNamespaceAndDefaultTtlAreExposed(): void
    {
        $interval = new DateInterval('PT5M');

        $this->assertSame('test', $this->createCache()->namespace());
        $this->assertNull($this->createCache()->defaultTtl());
        $this->assertSame(60, $this->createCache('test', 60)->defaultTtl());
        $this->assertSame($interval, $this->createCache('test', $interval)->defaultTtl());
    }

    #[DataProvider('validNamespaceProvider')]
    public function testValidNamespacesAreAccepted(string $namespace): void
    {
        $this->assertSame($namespace, $this->createCache($namespace)->namespace());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validNamespaceProvider(): iterable
    {
        yield 'lowercase letters' => ['pages'];
        yield 'digits' => ['123'];
        yield 'letters and digits' => ['cache2'];
        yield 'single character' => ['a'];
    }

    #[DataProvider('invalidNamespaceProvider')]
    public function testInvalidNamespacesAreRejected(string $namespace): void
    {
        $this->expectException(InvalidNamespaceException::class);
        $this->createCache($namespace);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidNamespaceProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'uppercase' => ['Pages'];
        yield 'hyphen' => ['my-cache'];
        yield 'underscore' => ['my_cache'];
        yield 'dot' => ['my.cache'];
        yield 'space' => ['my cache'];
        yield 'slash' => ['a/b'];
        yield 'backslash' => ['a\b'];
        yield 'parent directory' => ['..'];
        yield 'path traversal' => ['../escape'];
        yield 'trailing newline' => ["cache\n"];
        yield 'null byte' => ["cache\0"];
        yield 'non ASCII letters' => ['caché'];
    }

    public function testInvalidNamespaceExceptionsArePsrExceptions(): void
    {
        try {
            $this->createCache('Invalid');
            $this->fail('The namespace should have been rejected.');
        } catch (InvalidNamespaceException $exception) {
            $this->assertInstanceOf(PsrInvalidArgumentException::class, $exception);
            $this->assertStringContainsString('"Invalid"', $exception->getMessage());
        }
    }

    public function testMissingItemsReturnTheDefault(): void
    {
        $cache = $this->createCache();

        $this->assertNull($cache->get('missing'));
        $this->assertSame('fallback', $cache->get('missing', 'fallback'));
        $this->assertSame(0, $cache->get('missing', 0));
        $this->assertFalse($cache->has('missing'));
        $this->assertNull($cache->cachedTime('missing'));
        $this->assertNull($cache->getItem('missing'));
    }

    #[DataProvider('valueProvider')]
    public function testValuesAreStoredAndRetrievedUnchanged(mixed $value): void
    {
        $cache = $this->createCache();

        $this->assertTrue($cache->set('key', $value));

        $this->assertEquals($value, $cache->get('key', 'default'));
        $this->assertTrue($cache->has('key'));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function valueProvider(): iterable
    {
        yield 'string' => ['text'];
        yield 'empty string' => [''];
        yield 'multiline string' => ["line one\nline two\ttabbed"];
        yield 'string with quotes' => ['it\'s "quoted" and \ escaped'];
        yield 'string with PHP code' => ['<?php echo "code"; ?>'];
        yield 'string with a null byte' => ["a\0b"];
        yield 'unicode string' => ['èàù — 日本語 — 🚀'];
        yield 'integer' => [42];
        yield 'zero' => [0];
        yield 'negative integer' => [-7];
        yield 'float' => [1.5];
        yield 'float with a zero fraction' => [2.0];
        yield 'true' => [true];
        yield 'false' => [false];
        yield 'null' => [null];
        yield 'empty array' => [[]];
        yield 'list' => [[1, 'two', 3.5, null, false]];
        yield 'associative array' => [['a' => 1, 'b' => ['c' => 2]]];
        yield 'array with integer keys' => [[5 => 'a', 10 => 'b']];
        yield 'nested empty arrays' => [[[], [[]]]];
        yield 'stdClass' => [(object) ['a' => 1, 'b' => [2]]];
    }

    public function testFalseAndNullValuesAreDistinctFromMisses(): void
    {
        $cache = $this->createCache();
        $cache->set('false', false);
        $cache->set('null', null);

        $this->assertTrue($cache->has('false'));
        $this->assertTrue($cache->has('null'));
        $this->assertFalse($cache->get('false', 'default'));
        $this->assertNull($cache->get('null', 'default'));
    }

    public function testSettingAnExistingKeyReplacesTheValue(): void
    {
        $cache = $this->createCache();
        $cache->set('key', 'old');
        $cache->set('key', 'new');

        $this->assertSame('new', $cache->get('key'));
        $this->assertSame(1, $cache->count());
    }

    public function testKeysAreCaseSensitiveAndExact(): void
    {
        $cache = $this->createCache();
        $cache->set('key', 'lower');
        $cache->set('Key', 'upper');
        $cache->set('key ', 'trailing space');

        $this->assertSame('lower', $cache->get('key'));
        $this->assertSame('upper', $cache->get('Key'));
        $this->assertSame('trailing space', $cache->get('key '));
        $this->assertSame(3, $cache->count());
    }

    #[DataProvider('validKeyProvider')]
    public function testValidKeys(string $key): void
    {
        $cache = $this->createCache();

        $cache->set($key, 'value');

        $this->assertSame('value', $cache->get($key));
        $this->assertTrue($cache->delete($key));
        $this->assertFalse($cache->has($key));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validKeyProvider(): iterable
    {
        yield 'letters' => ['abc'];
        yield 'zero' => ['0'];
        yield 'dots' => ['..'];
        yield 'with spaces' => ['a b c'];
        yield 'dashes and underscores' => ['a-b_c'];
        yield 'unicode' => ['ключ-键'];
        yield 'symbols' => ['a#b$c%d&e*f+g=h'];
        yield 'quotes' => ['it\'s "quoted"'];
        yield 'very long' => [str_repeat('k', 2000)];
        yield 'newline' => ["a\nb"];
    }

    #[DataProvider('invalidKeyProvider')]
    public function testInvalidKeysAreRejectedByEveryOperation(string $key): void
    {
        $cache = $this->createCache();

        $operations = [
            'get'        => static fn() => $cache->get($key),
            'set'        => static fn() => $cache->set($key, 'value'),
            'has'        => static fn() => $cache->has($key),
            'delete'     => static fn() => $cache->delete($key),
            'cachedTime' => static fn() => $cache->cachedTime($key),
            'getItem'    => static fn() => $cache->getItem($key),
        ];

        foreach ($operations as $name => $operation) {
            try {
                $operation();
                $this->fail("{$name}() should reject the key.");
            } catch (InvalidKeyException $exception) {
                $this->assertInstanceOf(PsrInvalidArgumentException::class, $exception, $name);
            }
        }

        $this->assertSame(0, $cache->count());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidKeyProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'opening brace' => ['a{b'];
        yield 'closing brace' => ['a}b'];
        yield 'opening parenthesis' => ['a(b'];
        yield 'closing parenthesis' => ['a)b'];
        yield 'slash' => ['a/b'];
        yield 'backslash' => ['a\b'];
        yield 'at sign' => ['a@b'];
        yield 'colon' => ['a:b'];
        yield 'only a reserved character' => ['/'];
        yield 'path traversal' => ['../../escape'];
        yield 'reserved character at the start' => ['{key'];
        yield 'reserved character at the end' => ['key:'];
    }

    public function testInvalidKeysDoNotChangeTheCache(): void
    {
        $cache = $this->createCache();
        $cache->set('valid', 'value');

        try {
            $cache->set('in/valid', 'other');
        } catch (InvalidKeyException) {
        }

        $this->assertSame(['valid' => 'value'], $cache->getMultiple(['valid']));
        $this->assertSame(1, $cache->count());
    }

    public function testDeleteRemovesOnlyTheGivenItem(): void
    {
        $cache = $this->createCache();
        $cache->set('a', 1);
        $cache->set('b', 2);

        $this->assertTrue($cache->delete('a'));

        $this->assertFalse($cache->has('a'));
        $this->assertSame(2, $cache->get('b'));
        $this->assertSame(1, $cache->count());
    }

    public function testDeletingAMissingItemSucceeds(): void
    {
        $this->assertTrue($this->createCache()->delete('missing'));
    }

    public function testClearRemovesEveryItem(): void
    {
        $cache = $this->createCache();
        $cache->set('a', 1);
        $cache->set('b', 2);

        $this->assertTrue($cache->clear());

        $this->assertSame(0, $cache->count());
        $this->assertFalse($cache->has('a'));
        $this->assertNull($cache->get('b'));
    }

    public function testClearOnAnEmptyCacheSucceeds(): void
    {
        $cache = $this->createCache();

        $this->assertTrue($cache->clear());
        $this->assertSame(0, $cache->count());
    }

    public function testCacheCanBeUsedAfterBeingCleared(): void
    {
        $cache = $this->createCache();
        $cache->set('a', 1);
        $cache->clear();

        $cache->set('a', 2);

        $this->assertSame(2, $cache->get('a'));
        $this->assertSame(1, $cache->count());
    }

    public function testCountReflectsTheStoredItems(): void
    {
        $cache = $this->createCache();

        $this->assertSame(0, $cache->count());
        $this->assertCount(0, $cache);

        $cache->set('a', 1);
        $cache->set('b', 2);
        $cache->set('c', null);

        $this->assertSame(3, $cache->count());
        $this->assertCount(3, $cache);
    }

    public function testItemsWithoutTtlNeverExpire(): void
    {
        $cache = $this->createCache();

        $cache->set('key', 'value');

        $this->assertNull($cache->getItem('key')?->expirationTime());
        $this->assertFalse($cache->getItem('key')?->isExpired());
    }

    public function testExplicitTtlSetsTheExpirationTime(): void
    {
        $cache = $this->createCache();

        $cache->set('key', 'value', 100);

        $item = $cache->getItem('key');
        $this->assertNotNull($item);
        $this->assertSame($item->cachedTime() + 100, $item->expirationTime());
        $this->assertSame('value', $cache->get('key'));
    }

    public function testDateIntervalTtlSetsTheExpirationTime(): void
    {
        $cache = $this->createCache();

        $cache->set('key', 'value', new DateInterval('PT2H'));

        $item = $cache->getItem('key');
        $this->assertNotNull($item);
        $this->assertSame($item->cachedTime() + 7200, $item->expirationTime());
    }

    public function testDefaultTtlIsUsedWhenNoTtlIsGiven(): void
    {
        $cache = $this->createCache('test', 300);

        $cache->set('key', 'value');

        $item = $cache->getItem('key');
        $this->assertNotNull($item);
        $this->assertSame($item->cachedTime() + 300, $item->expirationTime());
    }

    public function testDefaultTtlAsDateIntervalIsUsedWhenNoTtlIsGiven(): void
    {
        $cache = $this->createCache('test', new DateInterval('PT10M'));

        $cache->set('key', 'value');

        $item = $cache->getItem('key');
        $this->assertNotNull($item);
        $this->assertSame($item->cachedTime() + 600, $item->expirationTime());
    }

    public function testExplicitTtlOverridesTheDefaultTtl(): void
    {
        $cache = $this->createCache('test', 300);

        $cache->set('key', 'value', 50);

        $item = $cache->getItem('key');
        $this->assertNotNull($item);
        $this->assertSame($item->cachedTime() + 50, $item->expirationTime());
    }

    public function testExpiredItemsAreMisses(): void
    {
        $cache = $this->createCache();
        $cache->set('key', 'value', 100);
        $cache->set('other', 'kept', 100);

        $this->expire($cache, 'key');

        $this->assertFalse($cache->has('key'));
        $this->assertNull($cache->get('key'));
        $this->assertSame('fallback', $cache->get('key', 'fallback'));
        $this->assertNull($cache->cachedTime('key'));
        $this->assertNull($cache->getItem('key'));
        $this->assertSame('kept', $cache->get('other'));
    }

    public function testExpiredItemsAreRemovedWhenAccessed(): void
    {
        $cache = $this->createCache();
        $cache->set('key', 'value', 100);
        $this->expire($cache, 'key');

        $cache->has('key');

        $this->assertSame(0, $cache->count());
    }

    public function testExpiredItemsCanBeSetAgain(): void
    {
        $cache = $this->createCache();
        $cache->set('key', 'old', 100);
        $this->expire($cache, 'key');

        $cache->set('key', 'new');

        $this->assertSame('new', $cache->get('key'));
    }

    #[DataProvider('nonPositiveTtlProvider')]
    public function testItemsWithANonPositiveTtlAreNotRetrievable(int|DateInterval $ttl): void
    {
        $cache = $this->createCache();

        $cache->set('key', 'value', $ttl);

        $this->assertFalse($cache->has('key'));
        $this->assertNull($cache->get('key'));
    }

    /**
     * @return iterable<string, array{DateInterval|int}>
     */
    public static function nonPositiveTtlProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
        yield 'large negative' => [-86400];
        yield 'negative interval' => [self::negativeInterval()];
    }

    public function testItemsWithANonPositiveTtlAreDeletedFromTheCache(): void
    {
        $cache = $this->createCache();
        $cache->set('key', 'old');

        $cache->set('key', 'new', 0);

        $this->assertSame(0, $cache->count(), 'A zero time-to-live must delete the item');
    }

    public function testCachedTimeIsTheTimeOfTheLastSet(): void
    {
        $cache = $this->createCache();
        $before = time();

        $cache->set('key', 'value');

        $cachedTime = $cache->cachedTime('key');
        $this->assertNotNull($cachedTime);
        $this->assertGreaterThanOrEqual($before, $cachedTime);
        $this->assertLessThanOrEqual(time(), $cachedTime);
    }

    public function testGetItemReturnsACacheItem(): void
    {
        $cache = $this->createCache();
        $cache->set('key', ['a' => 1], 60);

        $item = $cache->getItem('key');

        $this->assertInstanceOf(CacheItem::class, $item);
        $this->assertSame(['a' => 1], $item->value());
    }

    public function testGetMultipleReturnsValuesAndDefaults(): void
    {
        $cache = $this->createCache();
        $cache->set('a', 1);
        $cache->set('b', null);

        $this->assertSame(['a' => 1, 'b' => null, 'c' => 'default'], $cache->getMultiple(['a', 'b', 'c'], 'default'));
    }

    public function testGetMultipleKeepsTheOrderOfTheKeys(): void
    {
        $cache = $this->createCache();
        $cache->set('a', 1);
        $cache->set('b', 2);

        $this->assertSame(['b', 'a'], array_keys((array) $cache->getMultiple(['b', 'a'])));
    }

    public function testGetMultipleAcceptsTraversables(): void
    {
        $cache = $this->createCache();
        $cache->set('a', 1);

        $this->assertSame(['a' => 1], (array) $cache->getMultiple(new ArrayObject(['a'])));
        $this->assertSame([], (array) $cache->getMultiple([]));
    }

    public function testGetMultipleRejectsInvalidKeys(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->createCache()->getMultiple(['valid', 'in/valid']);
    }

    public function testSetMultipleStoresEveryValue(): void
    {
        $cache = $this->createCache();

        $this->assertTrue($cache->setMultiple(['a' => 1, 'b' => 2, 'c' => 3]));

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $cache->getMultiple(['a', 'b', 'c']));
        $this->assertSame(3, $cache->count());
    }

    public function testSetMultipleAppliesTheTtlToEveryValue(): void
    {
        $cache = $this->createCache();

        $cache->setMultiple(['a' => 1, 'b' => 2], 120);

        foreach (['a', 'b'] as $key) {
            $item = $cache->getItem($key);
            $this->assertNotNull($item);
            $this->assertSame($item->cachedTime() + 120, $item->expirationTime(), $key);
        }
    }

    public function testSetMultipleAcceptsGenerators(): void
    {
        $cache = $this->createCache();

        $cache->setMultiple((static function (): iterable {
            yield 'a' => 1;
            yield 'b' => 2;
        })());

        $this->assertSame(2, $cache->count());
    }

    public function testSetMultipleRejectsInvalidKeys(): void
    {
        $cache = $this->createCache();

        $this->expectException(InvalidKeyException::class);
        $cache->setMultiple(['valid' => 1, 'in/valid' => 2]);
    }

    public function testDeleteMultipleRemovesTheGivenItems(): void
    {
        $cache = $this->createCache();
        $cache->setMultiple(['a' => 1, 'b' => 2, 'c' => 3]);

        $this->assertTrue($cache->deleteMultiple(['a', 'c', 'missing']));

        $this->assertSame(['b' => 2], array_filter((array) $cache->getMultiple(['a', 'b', 'c'])));
        $this->assertSame(1, $cache->count());
    }

    public function testDeleteMultipleRejectsInvalidKeys(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->createCache()->deleteMultiple(['valid', 'in/valid']);
    }

    public function testHasMultiple(): void
    {
        $cache = $this->createCache();
        $cache->setMultiple(['a' => 1, 'b' => 2]);

        $this->assertTrue($cache->hasMultiple(['a', 'b']));
        $this->assertFalse($cache->hasMultiple(['a', 'missing']));
        $this->assertTrue($cache->hasMultiple([]));
    }

    public function testNamespacesDoNotShareItems(): void
    {
        $first = $this->createCache('first');
        $second = $this->createCache('second');

        $first->set('key', 'first value');
        $second->set('key', 'second value');

        $this->assertSame('first value', $first->get('key'));
        $this->assertSame('second value', $second->get('key'));

        $first->clear();

        $this->assertFalse($first->has('key'));
        $this->assertSame('second value', $second->get('key'));
    }

    public function testDeprecatedFetchReadsValues(): void
    {
        $cache = $this->createCache();
        $cache->set('key', 'value');

        $value = null;
        $messages = $this->captureDeprecations(static function () use ($cache, &$value): void {
            $value = $cache->fetch('key');
        });

        $this->assertSame('value', $value);
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('fetch() is deprecated since Formwork 2.4.0', $messages[0]);
    }

    public function testDeprecatedSaveStoresValues(): void
    {
        $cache = $this->createCache();

        $messages = $this->captureDeprecations(static function () use ($cache): void {
            $cache->save('key', 'value', 60);
        });

        $this->assertSame('value', $cache->get('key'));
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('save() is deprecated since Formwork 2.4.0', $messages[0]);
    }

    public function testDeprecatedFetchMultipleReadsValues(): void
    {
        $cache = $this->createCache();
        $cache->setMultiple(['a' => 1, 'b' => 2]);

        $values = null;
        $messages = $this->captureDeprecations(static function () use ($cache, &$values): void {
            $values = $cache->fetchMultiple(['a', 'b']);
        });

        $this->assertSame(['a' => 1, 'b' => 2], $values);
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('fetchMultiple() is deprecated since Formwork 2.4.0', $messages[0]);
    }

    public function testDeprecatedSaveMultipleStoresValues(): void
    {
        $cache = $this->createCache();

        $messages = $this->captureDeprecations(static function () use ($cache): void {
            $cache->saveMultiple(['a' => 1, 'b' => 2]);
        });

        $this->assertSame(2, $cache->count());
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('saveMultiple() is deprecated since Formwork 2.4.0', $messages[0]);
    }

    public function testObjectsCanBeCached(): void
    {
        $cache = $this->createCache();
        $object = new stdClass();
        $object->name = 'cached';

        $cache->set('object', $object);

        $this->assertEquals($object, $cache->get('object'));
    }

    /**
     * @return AbstractCache&CountableCache
     */
    abstract protected function createCache(string $namespace = 'test', int|DateInterval|null $defaultTtl = null): AbstractCache;

    /**
     * Make the given item expired as if its time-to-live had elapsed
     */
    abstract protected function expire(AbstractCache $cache, string $key): void;

    private static function negativeInterval(): DateInterval
    {
        $interval = new DateInterval('PT1H');
        $interval->invert = 1;

        return $interval;
    }
}
