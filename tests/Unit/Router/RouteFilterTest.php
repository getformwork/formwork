<?php

namespace Formwork\Tests\Unit\Router;

use Formwork\Router\RouteFilter;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RouteFilter::class)]
final class RouteFilterTest extends TestCase
{
    public function testConstructorExposesNameActionAndDefaults(): void
    {
        $action = static fn(): null => null;
        $routeFilter = new RouteFilter('auth', $action);

        $this->assertSame('auth', $routeFilter->getName());
        $this->assertSame($action, $routeFilter->getAction());
        $this->assertSame(['GET'], $routeFilter->getMethods());
        $this->assertSame(['HTTP'], $routeFilter->getTypes());
        $this->assertSame('', $routeFilter->getPrefix());
    }

    public function testMethodsTypesAndPrefixAreFluent(): void
    {
        $routeFilter = new RouteFilter('api', 'Filter@handle');

        $this->assertSame($routeFilter, $routeFilter->methods('GET', 'POST'));
        $this->assertSame(['GET', 'POST'], $routeFilter->getMethods());
        $this->assertSame($routeFilter, $routeFilter->types('XHR'));
        $this->assertSame(['XHR'], $routeFilter->getTypes());
        $this->assertSame($routeFilter, $routeFilter->prefix('/admin'));
        $this->assertSame('/admin', $routeFilter->getPrefix());
    }

    public function testNamedArgumentsAreRejectedByMethods(): void
    {
        $routeFilter = new RouteFilter('api', 'Filter@handle');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Formwork\Router\RouteFilter::methods() accepts only unnamed arguments');
        $routeFilter->methods(method: 'GET');
    }

    public function testNamedArgumentsAreRejectedByTypes(): void
    {
        $routeFilter = new RouteFilter('api', 'Filter@handle');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Formwork\Router\RouteFilter::types() accepts only unnamed arguments');
        $routeFilter->types(type: 'HTTP');
    }
}
