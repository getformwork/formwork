<?php

namespace Formwork\Tests\Data;

use Formwork\Data\Collection;
use Formwork\Data\CollectionDataProxy;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CollectionDataProxy::class)]
final class CollectionDataProxyTest extends TestCase
{
    public function testEveryItem(): void
    {
        $collection = Collection::from([
            'item1' => (object) ['key' => 'value1'],
            'item2' => (object) ['key' => 'value2'],
        ]);

        $this->assertInstanceOf(CollectionDataProxy::class, $collection->everyItem());
    }

    public function testPropertyGet(): void
    {
        $collection = Collection::from([
            'item1' => (object) ['key' => 'value1'],
            'item2' => (object) ['key' => 'value2'],
        ]);

        $this->assertSame([
            'item1' => 'value1',
            'item2' => 'value2',
        ], $collection->everyItem()->key->toArray());
    }

    public function testPropertySet(): void
    {
        $collection = Collection::from([
            'item1' => (object) ['key' => 'value1'],
            'item2' => (object) ['key' => 'value2'],
        ]);

        $collection->everyItem()->key = 'newValue';

        $this->assertSame([
            'item1' => 'newValue',
            'item2' => 'newValue',
        ], $collection->everyItem()->key->toArray());
    }

    public function testMethodCall(): void
    {
        $collection = Collection::from([
            'item1' => new class {
                public function greet(): string
                {
                    return 'Hello from item1';
                }
            },
            'item2' => new class {
                public function greet(): string
                {
                    return 'Hello from item2';
                }
            },
        ], typed: false);

        $this->assertSame([
            'item1' => 'Hello from item1',
            'item2' => 'Hello from item2',
        ], $collection->everyItem()->greet()->toArray());
    }
}
