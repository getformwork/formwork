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

    public function testCollectionIsAssociativeAndTyped(): void
    {
        $pluginCollection = new PluginCollection([]);

        $this->assertTrue($pluginCollection->isAssociative());
        $this->assertSame(Plugin::class, $pluginCollection->dataType());
    }

    public function testOnlyPluginsAreAccepted(): void
    {
        $logicException = $this->captureException(LogicException::class, fn() => new PluginCollection($this->unchecked(['demo' => new stdClass()])));

        $this->assertInstanceOf(LogicException::class, $logicException);
    }
}
