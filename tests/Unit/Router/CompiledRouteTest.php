<?php

namespace Formwork\Tests\Unit\Router;

use Formwork\Events\EventDispatcher;
use Formwork\Http\Request;
use Formwork\Router\CompiledRoute;
use Formwork\Router\Exceptions\InvalidRouteException;
use Formwork\Router\Route;
use Formwork\Router\Router;
use Formwork\Services\Container;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(CompiledRoute::class)]
#[CoversClass(Router::class)]
final class CompiledRouteTest extends TestCase
{
    public function testAccessorsExposeCompiledRouteData(): void
    {
        $compiled = new CompiledRoute('/users/{id}', '~^/users/([^/]+)$~', ['id']);

        $this->assertSame('/users/{id}', $compiled->path());
        $this->assertSame('~^/users/([^/]+)$~', $compiled->regex());
        $this->assertSame(['id'], $compiled->params());
    }

    public function testCompilationNormalizesThePathAndListsParametersInOrder(): void
    {
        $route = (new Route('user', 'users/{id:digits}/{tab}?'))->prefix('admin');

        $compiled = $this->compile($route);

        $this->assertSame('/admin/users/{id:digits}/{tab}?/', $compiled->path());
        $this->assertSame(['id', 'tab'], $compiled->params());
    }

    public function testCompilationRejectsParametersWithoutSeparator(): void
    {
        $this->expectException(InvalidRouteException::class);
        $this->expectExceptionMessage('Parameter "id" must be preceded by a separator');
        $this->compile(new Route('invalid', '/users{id}'));
    }

    public function testCompilationRejectsRepeatedParameters(): void
    {
        $this->expectException(InvalidRouteException::class);
        $this->expectExceptionMessage('Parameter "id" cannot be used more than once');
        $this->compile(new Route('invalid', '/users/{id}/{id}'));
    }

    /**
     * @param array<int, ?string> $expectedGroups
     */
    #[DataProvider('compiledRegexProvider')]
    public function testCompiledRegexMatchesTheWholePathOnly(string $path, string $subject, bool $matches, array $expectedGroups = []): void
    {
        $compiled = $this->compile(new Route('test', $path));

        $this->assertSame($matches, (bool) preg_match($compiled->regex(), $subject, $groups, PREG_UNMATCHED_AS_NULL));

        if ($matches) {
            $this->assertSame($expectedGroups, array_slice($groups, 1));
        }
    }

    /**
     * @return iterable<string, array{string, string, bool, 3?: array<int, ?string>}>
     */
    public static function compiledRegexProvider(): iterable
    {
        yield 'static path' => ['/docs/', '/docs/', true];
        yield 'static path with a suffix' => ['/docs/', '/docs/extra/', false];
        yield 'static path with a prefix' => ['/docs/', '/other/docs/', false];
        yield 'static dot is matched literally' => ['/sitemap.xml', '/sitemap.xml/', true];
        yield 'static dot does not match other characters' => ['/sitemap.xml', '/sitemapxxml/', false];
        yield 'required parameter' => ['/users/{id:digits}', '/users/42/', true, ['42']];
        yield 'required parameter missing' => ['/users/{id:digits}', '/users/', false];
        yield 'required parameter invalid' => ['/users/{id:digits}', '/users/abc/', false];
        yield 'optional parameter present' => ['/archive/{year:digits}?', '/archive/2025/', true, ['2025']];
        yield 'optional parameter omitted' => ['/archive/{year:digits}?', '/archive/', true, [null]];
        yield 'custom separator' => ['/search,{term}', '/search,php/', true, ['php']];
        yield 'alternation in the pattern stays inside its group' => ['/lang/{code:en|it}', '/lang/it/', true, ['it']];
        yield 'alternation does not escape the whole path' => ['/lang/{code:en|it}', '/other/en/', false];
    }

    private function compile(Route $route): CompiledRoute
    {
        $router = new class (new Container(), new Request([], [], [], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']), new EventDispatcher()) extends Router {
            public function compile(Route $route): CompiledRoute
            {
                return $this->compileRoute($route);
            }
        };

        return $router->compile($route);
    }
}
