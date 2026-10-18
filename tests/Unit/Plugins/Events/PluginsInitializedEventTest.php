<?php

namespace Formwork\Tests\Unit\Plugins\Events;

use Formwork\Events\Event;
use Formwork\Plugins\Events\PluginsInitializedEvent;
use Formwork\Plugins\Plugins;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PluginsInitializedEvent::class)]
final class PluginsInitializedEventTest extends TestCase
{
    public function testEventExposesThePlugins(): void
    {
        $plugins = $this->createStub(Plugins::class);

        $pluginsInitializedEvent = new PluginsInitializedEvent($plugins);

        $this->assertInstanceOf(Event::class, $pluginsInitializedEvent);
        $this->assertSame('pluginsInitialized', $pluginsInitializedEvent->name());
        $this->assertSame($plugins, $pluginsInitializedEvent->plugins());
        $this->assertSame(['plugins' => $plugins], $pluginsInitializedEvent->data());
    }
}
