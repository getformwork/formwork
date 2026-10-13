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
        $params = new RouteParams(['id' => '42', 'filters' => ['published' => true]]);

        $this->assertTrue($params->has('id'));
        $this->assertFalse($params->has('missing'));
        $this->assertSame('42', $params->get('id'));
        $this->assertSame('fallback', $params->get('missing', 'fallback'));
        $this->assertSame(['published' => true], $params->get('filters'));
        $this->assertSame(['id' => '42', 'filters' => ['published' => true]], $params->toArray());
    }

    public function testEmptyRouteParamsHaveNoValues(): void
    {
        $params = new RouteParams([]);

        $this->assertFalse($params->has('id'));
        $this->assertNull($params->get('id'));
        $this->assertSame([], $params->toArray());
    }
}
