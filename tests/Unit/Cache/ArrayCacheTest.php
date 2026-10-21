<?php

namespace Formwork\Tests\Unit\Cache;

use DateInterval;
use Formwork\Cache\AbstractCache;
use Formwork\Cache\ArrayCache;
use Formwork\Cache\CacheItem;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use stdClass;

#[CoversClass(ArrayCache::class)]
#[CoversClass(AbstractCache::class)]
final class ArrayCacheTest extends CacheTestCase
{
    public function testItemsAreNotSharedBetweenInstances(): void
    {
        $first = new ArrayCache();
        $second = new ArrayCache();

        $first->set('key', 'value');

        $this->assertFalse($second->has('key'));
    }

    public function testItemsAreKeptInMemoryAsTheyAre(): void
    {
        $arrayCache = new ArrayCache();
        $object = new stdClass();

        $arrayCache->set('object', $object);

        $this->assertSame($object, $arrayCache->get('object'));
    }

    protected function createCache(int|DateInterval|null $defaultTtl = null): AbstractCache
    {
        return new ArrayCache($defaultTtl);
    }

    protected function expire(AbstractCache $cache, string $key): void
    {
        $reflectionProperty = new ReflectionProperty(ArrayCache::class, 'items');
        $items = $reflectionProperty->getValue($cache);
        $item = $items[$key];
        $items[$key] = new CacheItem($item->value(), time() - 10, $item->cachedTime() - 100);
        $reflectionProperty->setValue($cache, $items);
    }
}
