<?php

namespace Formwork\Tests\Unit\Plugins;

use Formwork\Plugins\Plugin;
use Formwork\Plugins\PluginCollection;
use Formwork\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PluginCollection::class)]
final class PluginCollectionTest extends TestCase
{
    public function testPluginsAreIndexedByName(): void
    {
        $plugin = $this->createStub(Plugin::class);

        $collection = new PluginCollection(['demo' => $plugin]);

        $this->assertSame(['demo'], $collection->keys());
        $this->assertSame($plugin, $collection->get('demo'));
    }

    public function testCollectionIsAssociativeAndTyped(): void
    {
        $collection = new PluginCollection([]);

        $this->assertTrue($collection->isAssociative());
        $this->assertSame(Plugin::class, $collection->dataType());
    }

    public function testOnlyPluginsAreAccepted(): void
    {
        $this->expectException(LogicException::class);

        new PluginCollection(['demo' => new \stdClass()]);
    }
}
