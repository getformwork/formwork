<?php

namespace Formwork\Tests\Unit\Router;

use Formwork\Router\Route;
use Formwork\Router\RouteCollection;
use Formwork\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

#[CoversClass(RouteCollection::class)]
final class RouteCollectionTest extends TestCase
{
    public function testCollectionIsTypedAssociativeAndMutable(): void
    {
        $routeCollection = new RouteCollection();

        $this->assertTrue($routeCollection->isAssociative());
        $this->assertTrue($routeCollection->isTyped());
        $this->assertTrue($routeCollection->isMutable());
        $this->assertSame(Route::class, $routeCollection->dataType());
        $this->assertTrue($routeCollection->isEmpty());
    }

    public function testAddIndexesRoutesByNameAndReplacesExistingNames(): void
    {
        $first = new Route('home', '/');
        $replacement = new Route('home', '/start');
        $other = new Route('about', '/about');
        $routeCollection = new RouteCollection();

        $routeCollection->add($first);
        $routeCollection->add($other);
        $routeCollection->add($replacement);

        $this->assertCount(2, $routeCollection);
        $this->assertSame($replacement, $routeCollection->get('home'));
        $this->assertSame($other, $routeCollection->get('about'));
        $this->assertSame(['home', 'about'], $routeCollection->keys());
    }

    public function testConstructorRejectsNonRouteItems(): void
    {
        $logicException = $this->captureException(LogicException::class, fn() => new RouteCollection($this->unchecked(['home' => new stdClass()])));

        $this->assertSame('Typed collections cannot be created from data of different types', $logicException->getMessage());
    }
}
