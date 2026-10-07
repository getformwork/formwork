<?php

namespace Formwork\Tests\Unit\Router;

use Formwork\Router\Route;
use Formwork\Router\RouteCollection;
use Formwork\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RouteCollection::class)]
final class RouteCollectionTest extends TestCase
{
    public function testCollectionIsTypedAssociativeAndMutable(): void
    {
        $collection = new RouteCollection();

        $this->assertTrue($collection->isAssociative());
        $this->assertTrue($collection->isTyped());
        $this->assertTrue($collection->isMutable());
        $this->assertSame(Route::class, $collection->dataType());
        $this->assertTrue($collection->isEmpty());
    }

    public function testAddIndexesRoutesByNameAndReplacesExistingNames(): void
    {
        $first = new Route('home', '/');
        $replacement = new Route('home', '/start');
        $other = new Route('about', '/about');
        $collection = new RouteCollection();

        $collection->add($first);
        $collection->add($other);
        $collection->add($replacement);

        $this->assertCount(2, $collection);
        $this->assertSame($replacement, $collection->get('home'));
        $this->assertSame($other, $collection->get('about'));
        $this->assertSame(['home', 'about'], $collection->keys());
    }

    public function testConstructorRejectsNonRouteItems(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Typed collections cannot be created from data of different types');
        new RouteCollection(['home' => new \stdClass()]);
    }
}
