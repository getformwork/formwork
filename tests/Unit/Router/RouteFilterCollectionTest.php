<?php

namespace Formwork\Tests\Unit\Router;

use Formwork\Router\RouteFilter;
use Formwork\Router\RouteFilterCollection;
use Formwork\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

#[CoversClass(RouteFilterCollection::class)]
final class RouteFilterCollectionTest extends TestCase
{
    public function testCollectionIsTypedAssociativeAndMutable(): void
    {
        $routeFilterCollection = new RouteFilterCollection();

        $this->assertTrue($routeFilterCollection->isAssociative());
        $this->assertTrue($routeFilterCollection->isTyped());
        $this->assertTrue($routeFilterCollection->isMutable());
        $this->assertSame(RouteFilter::class, $routeFilterCollection->dataType());
    }

    public function testAddIndexesFiltersByName(): void
    {
        $authentication = new RouteFilter('auth', 'Auth@handle');
        $logging = new RouteFilter('logging', 'Log@handle');
        $routeFilterCollection = new RouteFilterCollection();

        $routeFilterCollection->add($authentication);
        $routeFilterCollection->add($logging);

        $this->assertCount(2, $routeFilterCollection);
        $this->assertSame($authentication, $routeFilterCollection->get('auth'));
        $this->assertSame($logging, $routeFilterCollection->get('logging'));
    }

    public function testConstructorRejectsNonFilterItems(): void
    {
        $logicException = $this->captureException(LogicException::class, fn() => new RouteFilterCollection($this->unchecked(['auth' => new stdClass()])));

        $this->assertSame('Typed collections cannot be created from data of different types', $logicException->getMessage());
    }
}
