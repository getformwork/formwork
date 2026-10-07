<?php

namespace Formwork\Tests\Unit\Router;

use Formwork\Router\RouteFilter;
use Formwork\Router\RouteFilterCollection;
use Formwork\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RouteFilterCollection::class)]
final class RouteFilterCollectionTest extends TestCase
{
    public function testCollectionIsTypedAssociativeAndMutable(): void
    {
        $collection = new RouteFilterCollection();

        $this->assertTrue($collection->isAssociative());
        $this->assertTrue($collection->isTyped());
        $this->assertTrue($collection->isMutable());
        $this->assertSame(RouteFilter::class, $collection->dataType());
    }

    public function testAddIndexesFiltersByName(): void
    {
        $authentication = new RouteFilter('auth', 'Auth@handle');
        $logging = new RouteFilter('logging', 'Log@handle');
        $collection = new RouteFilterCollection();

        $collection->add($authentication);
        $collection->add($logging);

        $this->assertCount(2, $collection);
        $this->assertSame($authentication, $collection->get('auth'));
        $this->assertSame($logging, $collection->get('logging'));
    }

    public function testConstructorRejectsNonFilterItems(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Typed collections cannot be created from data of different types');
        new RouteFilterCollection(['auth' => new \stdClass()]);
    }
}
