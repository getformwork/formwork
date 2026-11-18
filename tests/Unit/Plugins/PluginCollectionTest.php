<?php

namespace Formwork\Tests\Unit\Plugins;

use Formwork\Plugins\Plugin;
use Formwork\Plugins\PluginCollection;
use Formwork\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

#[CoversClass(PluginCollection::class)]
final class PluginCollectionTest extends TestCase
{
    public function testPluginsAreIndexedByName(): void
    {
        $plugin = $this->createStub(Plugin::class);

        $pluginCollection = new PluginCollection(['demo' => $plugin]);

        $this->assertSame(['demo'], $pluginCollection->keys());
        $this->assertSame($plugin, $pluginCollection->get('demo'));
    }

    public function testOnlyPluginsAreAccepted(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Typed collections cannot be created from data of different types');

        new PluginCollection($this->unchecked(['demo' => new stdClass()]));
    }

    public function testItemsThatAreNotPluginsAreRejected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Typed collections cannot be created from data of different types');

        new PluginCollection($this->unchecked(['item' => new stdClass()]));
    }
}
