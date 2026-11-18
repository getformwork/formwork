<?php

namespace Formwork\Tests\Unit\Cache;

use DateInterval;
use Formwork\Cache\AbstractCache;
use Formwork\Cache\ArrayCache;
use Formwork\Cache\CacheItem;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use stdClass;

#[CoversClass(ArrayCache::class)]
#[CoversClass(AbstractCache::class)]
final class ArrayCacheTest extends TestCase
{
    use ProvidesCacheTests;

    public function testItemsAreNotSharedBetweenInstances(): void
    {
        $first = new ArrayCache();
        $second = new ArrayCache();

        $first->set('key', 'value');

        $this->assertFalse($second->has('key'));
    }

    public function testItemsArePreservedInMemoryAsTheyAre(): void
    {
        $arrayCache = new ArrayCache();
        $object = new stdClass();

        $arrayCache->set('object', $object);

        $this->assertSame($object, $arrayCache->get('object'));
    }

    public function testMultipleOperationsReportASingleFailureButProcessTheOtherKeys(): void
    {
        // A cache that cannot store or delete the `failing` key
        $arrayCache = new class extends ArrayCache {
            public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
            {
                return $key !== 'failing' && parent::set($key, $value, $ttl);
            }

            public function delete(string $key): bool
            {
                return $key !== 'failing' && parent::delete($key);
            }
        };

        $this->assertFalse($arrayCache->setMultiple(['failing' => 1, 'other' => 2]));
        $this->assertSame(2, $arrayCache->get('other'));

        $this->assertFalse($arrayCache->deleteMultiple(['failing', 'other']));
        $this->assertFalse($arrayCache->has('other'));
    }

    protected function createCache(int|DateInterval|null $defaultTtl = null): ArrayCache
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
