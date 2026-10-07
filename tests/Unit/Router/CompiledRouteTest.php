<?php

namespace Formwork\Tests\Unit\Router;

use Formwork\Router\CompiledRoute;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CompiledRoute::class)]
final class CompiledRouteTest extends TestCase
{
    public function testConstructorAndAccessorsExposeCompiledRouteData(): void
    {
        $compiled = new CompiledRoute('/users/{id}', '~^/users/([^/]+)$~', ['id']);

        $this->assertSame('/users/{id}', $compiled->path());
        $this->assertSame('~^/users/([^/]+)$~', $compiled->regex());
        $this->assertSame(['id'], $compiled->params());
    }
}
