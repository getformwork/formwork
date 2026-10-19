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
        $routeActionResolvedEvent = new RouteActionResolvedEvent($route, $action);

        $this->assertSame('routeActionResolved', $routeActionResolvedEvent->name());
        $this->assertSame($route, $routeActionResolvedEvent->route());
        $this->assertSame($action, $routeActionResolvedEvent->action());
        $this->assertSame(['route' => $route, 'action' => $action], $routeActionResolvedEvent->data());
    }

    public function testActionCanBeReplaced(): void
    {
        $first = static fn(): null => null;
        $second = static fn(): null => null;
        $routeActionResolvedEvent = new RouteActionResolvedEvent(new Route('home', '/'), $first);

        $routeActionResolvedEvent->setAction($second);

        $this->assertSame($second, $routeActionResolvedEvent->action());
    }
}
