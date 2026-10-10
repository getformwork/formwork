<?php

namespace Formwork\Tests\Unit\Cache;

use Formwork\Cache\CacheItem;
use Formwork\Cache\CacheItemInterface;
use Formwork\Data\Contracts\ArraySerializable;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CacheItem::class)]
final class CacheItemTest extends TestCase
{
    public function testItemExposesItsData(): void
    {
        $item = new CacheItem(['value'], 2000000000, 1900000000);

        $this->assertInstanceOf(CacheItemInterface::class, $item);
        $this->assertInstanceOf(ArraySerializable::class, $item);
        $this->assertSame(['value'], $item->value());
        $this->assertSame(2000000000, $item->expirationTime());
        $this->assertSame(1900000000, $item->cachedTime());
    }

    public function testItemsWithoutExpirationTimeNeverExpire(): void
    {
        $item = new CacheItem('value', null, 0);

        $this->assertNull($item->expirationTime());
        $this->assertFalse($item->isExpired());
    }

    public function testItemsWithAFutureExpirationTimeAreNotExpired(): void
    {
        $this->assertFalse((new CacheItem('value', time() + 100, time()))->isExpired());
    }

    public function testItemsWithAPastExpirationTimeAreExpired(): void
    {
        $this->assertTrue((new CacheItem('value', time() - 100, time() - 200))->isExpired());
    }

    public function testItemsExpireAtTheirExpirationTime(): void
    {
        $this->assertTrue((new CacheItem('value', time(), time() - 10))->isExpired());
    }

    public function testItemsExpireWhenTheExpirationTimeIsZero(): void
    {
        $this->assertTrue((new CacheItem('value', 0, 0))->isExpired());
    }

    public function testItemsAreSerializedToArrays(): void
    {
        $item = new CacheItem('value', 10, 5);

        $this->assertSame(['value' => 'value', 'expirationTime' => 10, 'cachedTime' => 5], $item->toArray());
    }

    public function testItemsAreRestoredFromArrays(): void
    {
        $item = CacheItem::fromArray(['value' => ['a' => 1], 'expirationTime' => 10, 'cachedTime' => 5]);

        $this->assertEquals(new CacheItem(['a' => 1], 10, 5), $item);
    }

    public function testItemsWithoutExpirationTimeAreRestoredFromArrays(): void
    {
        $original = new CacheItem('value', null, 5);

        $restored = CacheItem::fromArray($original->toArray());

        $this->assertNull($restored->expirationTime());
        $this->assertEquals($original, $restored);
    }

    public function testFalseyValuesSurviveTheRoundTrip(): void
    {
        foreach ([0, '', false, null, []] as $value) {
            $this->assertSame($value, CacheItem::fromArray((new CacheItem($value, null, 1))->toArray())->value());
        }
    }
}
