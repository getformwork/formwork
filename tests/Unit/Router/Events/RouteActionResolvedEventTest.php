<?php

namespace Formwork\Tests\Unit\Router\Events;

use Formwork\Router\Events\RouteActionResolvedEvent;
use Formwork\Router\Route;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RouteActionResolvedEvent::class)]
final class RouteActionResolvedEventTest extends TestCase
{
    public function testEventExposesRouteAndAction(): void
    {
        $route = new Route('home', '/');
        $action = static fn(): null => null;
        $event = new RouteActionResolvedEvent($route, $action);

        $this->assertSame('routeActionResolved', $event->name());
        $this->assertSame($route, $event->route());
        $this->assertSame($action, $event->action());
        $this->assertSame(['route' => $route, 'action' => $action], $event->data());
    }

    public function testActionCanBeReplaced(): void
    {
        $first = static fn(): null => null;
        $second = static fn(): null => null;
        $event = new RouteActionResolvedEvent(new Route('home', '/'), $first);

        $event->setAction($second);

        $this->assertSame($second, $event->action());
    }
}
