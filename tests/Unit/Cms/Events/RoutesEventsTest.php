<?php

namespace Formwork\Tests\Unit\Cms\Events;

use Formwork\Cms\Events\RoutesAfterLoadEvent;
use Formwork\Cms\Events\RoutesBeforeLoadEvent;
use Formwork\Router\Router;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(RoutesBeforeLoadEvent::class)]
#[CoversClass(RoutesAfterLoadEvent::class)]
final class RoutesEventsTest extends TestCase
{
    /**
     * @param class-string<RoutesAfterLoadEvent|RoutesBeforeLoadEvent> $class
     */
    #[DataProvider('eventProvider')]
    public function testEventsExposeTheRouter(string $class, string $name): void
    {
        $router = $this->createStub(Router::class);

        $event = new $class($router);

        $this->assertSame($name, $event->name());
        $this->assertSame($router, $event->router());
        $this->assertSame(['router' => $router], $event->data());
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function eventProvider(): iterable
    {
        yield 'before load' => [RoutesBeforeLoadEvent::class, 'routesBeforeLoad'];
        yield 'after load' => [RoutesAfterLoadEvent::class, 'routesAfterLoad'];
    }
}
