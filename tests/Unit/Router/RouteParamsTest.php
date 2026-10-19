<?php

namespace Formwork\Tests\Unit\Router;

use Formwork\Router\RouteParams;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RouteParams::class)]
final class RouteParamsTest extends TestCase
{
    public function testRouteParamsExposeTheirDataThroughTheArrayableAndGetterContracts(): void
    {
        $routeParams = new RouteParams(['id' => '42', 'filters' => ['published' => true]]);

        $this->assertTrue($routeParams->has('id'));
        $this->assertFalse($routeParams->has('missing'));
        $this->assertSame('42', $routeParams->get('id'));
        $this->assertSame('fallback', $routeParams->get('missing', 'fallback'));
        $this->assertSame(['published' => true], $routeParams->get('filters'));
        $this->assertSame(['id' => '42', 'filters' => ['published' => true]], $routeParams->toArray());
    }

    public function testEmptyRouteParamsHaveNoValues(): void
    {
        $routeParams = new RouteParams([]);

        $this->assertFalse($routeParams->has('id'));
        $this->assertNull($routeParams->get('id'));
        $this->assertSame([], $routeParams->toArray());
    }
}
