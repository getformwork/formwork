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
        $filter = new RouteFilter('auth', $action);

        $this->assertSame('auth', $filter->getName());
        $this->assertSame($action, $filter->getAction());
        $this->assertSame(['GET'], $filter->getMethods());
        $this->assertSame(['HTTP'], $filter->getTypes());
        $this->assertSame('', $filter->getPrefix());
    }

    public function testMethodsTypesAndPrefixAreFluent(): void
    {
        $filter = new RouteFilter('api', 'Filter@handle');

        $this->assertSame($filter, $filter->methods('GET', 'POST'));
        $this->assertSame(['GET', 'POST'], $filter->getMethods());
        $this->assertSame($filter, $filter->types('XHR'));
        $this->assertSame(['XHR'], $filter->getTypes());
        $this->assertSame($filter, $filter->prefix('/admin'));
        $this->assertSame('/admin', $filter->getPrefix());
    }

    public function testNamedArgumentsAreRejectedByMethods(): void
    {
        $filter = new RouteFilter('api', 'Filter@handle');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Formwork\Router\RouteFilter::methods() accepts only unnamed arguments');
        $filter->methods(method: 'GET');
    }

    public function testNamedArgumentsAreRejectedByTypes(): void
    {
        $filter = new RouteFilter('api', 'Filter@handle');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Formwork\Router\RouteFilter::types() accepts only unnamed arguments');
        $filter->types(type: 'HTTP');
    }
}
