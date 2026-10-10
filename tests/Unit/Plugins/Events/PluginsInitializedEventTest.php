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

        $event = new PluginsInitializedEvent($plugins);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('pluginsInitialized', $event->name());
        $this->assertSame($plugins, $event->plugins());
        $this->assertSame(['plugins' => $plugins], $event->data());
    }
}
