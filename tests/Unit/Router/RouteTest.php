<?php

namespace Formwork\Tests\Unit\Router;

use Formwork\Events\EventDispatcher;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Router\Exceptions\RouteNotFoundException;
use Formwork\Router\Route;
use Formwork\Router\RouteParams;
use Formwork\Router\Router;
use Formwork\Services\Container;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Route::class)]
final class RouteTest extends TestCase
{
    public function testConstructorAndDefaultProperties(): void
    {
        $route = new Route('home', '/');

        $this->assertSame('home', $route->getName());
        $this->assertSame('/', $route->getPath());
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame(['HTTP'], $route->getTypes());
        $this->assertSame('', $route->getPrefix());
        $this->assertSame([], $route->getConstraints());
        $this->assertSame([], $route->getActionParameters());
    }

    #[DataProvider('pathProvider')]
    public function testConstructorPreservesNameAndPath(string $name, string $path): void
    {
        $route = new Route($name, $path);

        $this->assertSame($name, $route->getName());
        $this->assertSame($path, $route->getPath());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function pathProvider(): array
    {
        return [
            'empty path'     => ['empty', ''],
            'root path'      => ['root', '/'],
            'trailing slash' => ['docs', '/docs/'],
        ];
    }

    public function testActionAndActionParametersAreFluentAndReadable(): void
    {
        $action = static fn(): Response => new Response('ok');
        $parameters = ['layout' => 'minimal', 'cache' => false];
        $route = (new Route('home', '/'))->action($action)->actionParameters($parameters);

        $this->assertSame($route, $route->action($action));
        $this->assertSame($action, $route->getAction());
        $this->assertSame($parameters, $route->getActionParameters());

        $route->action('Controller@show');
        $this->assertSame('Controller@show', $route->getAction());
    }

    public function testMethodsAreConfigurableAndReplaceTheDefaults(): void
    {
        $route = new Route('submit', '/submit');

        $this->assertSame($route, $route->methods('POST', 'PUT'));
        $this->assertSame(['POST', 'PUT'], $route->getMethods());

        $route->methods();
        $this->assertSame([], $route->getMethods());
    }

    public function testMethodsRejectNamedArguments(): void
    {
        $route = new Route('submit', '/submit');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Formwork\Router\Route::methods() accepts only unnamed arguments');

        $route->methods(method: 'POST');
    }

    public function testTypesAreConfigurableAndReplaceTheDefaults(): void
    {
        $route = new Route('endpoint', '/endpoint');

        $this->assertSame($route, $route->types('HTTP', 'XHR'));
        $this->assertSame(['HTTP', 'XHR'], $route->getTypes());

        $route->types();
        $this->assertSame([], $route->getTypes());
    }

    public function testTypesRejectNamedArguments(): void
    {
        $route = new Route('endpoint', '/endpoint');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Formwork\Router\Route::types() accepts only unnamed arguments');

        $route->types(type: 'XHR');
    }

    public function testPrefixIsFluentAndReadable(): void
    {
        $route = new Route('admin.home', '/');

        $this->assertSame($route, $route->prefix('/admin'));
        $this->assertSame('/admin', $route->getPrefix());
    }

    public function testParameterConstraintsAcceptArraysAndClosures(): void
    {
        $constraint = static fn(string $value): bool => $value !== '';
        $route = new Route('user', '/users/{id}');

        $this->assertSame($route, $route->where('id', ['42', '43']));
        $this->assertSame(['id' => ['42', '43']], $route->getConstraints());

        $route->where('id', $constraint);
        $this->assertSame(['id' => $constraint], $route->getConstraints());
    }

    #[DataProvider('matchingProvider')]
    public function testRouteMatchingExposesStaticAndExtractedParameters(
        string $routePath,
        string $requestPath,
        array $expectedParams,
    ): void {
        $router = $this->router($requestPath);
        $route = $router->addRoute('test', $routePath)->action(
            static fn(RouteParams $params): Response => new Response((string) json_encode($params->toArray()))
        );

        $response = $router->dispatch();

        $this->assertSame($route, $router->current());
        $this->assertSame($expectedParams, $router->params()->toArray());
        $this->assertSame($expectedParams, json_decode($response->content(), true));
    }

    /**
     * @return array<string, array{string, string, array<string, string>}>
     */
    public static function matchingProvider(): array
    {
        return [
            'static root'                     => ['/', '/', []],
            'static path with trailing slash' => ['/docs/', '/docs/', []],
            'dynamic digits parameter'        => ['/users/{id:digits}', '/users/42/', ['id' => '42']],
            'optional parameter omitted'      => ['/archive/{year:digits}?', '/archive/', []],
            'optional parameter present'      => ['/archive/{year:digits}?', '/archive/2025/', ['year' => '2025']],
        ];
    }

    public function testRouteConstraintsControlMatching(): void
    {
        $router = $this->router('/users/42/');
        $route = $router->addRoute('user', '/users/{id}')->action(
            static fn(): Response => new Response('matched')
        )->where('id', ['42']);

        $this->assertSame('matched', $router->dispatch()->content());
        $this->assertSame($route, $router->current());

        $router = $this->router('/users/41/');
        $router->addRoute('user', '/users/{id}')->action(
            static fn(): Response => new Response('matched')
        )->where('id', static fn(string $value): bool => $value === '42');

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('No route matches with "/users/41/"');
        $router->dispatch();
    }

    #[DataProvider('requirementsProvider')]
    public function testRouteMethodTypeAndPrefixRequirementsParticipateInMatching(
        string $method,
        ?string $requestedWith,
        string $routePrefix,
        array $routeMethods,
        array $routeTypes,
        bool $matches,
    ): void {
        $router = $this->router('/admin/dashboard/', $method, $requestedWith);
        $router->addRoute('dashboard', '/dashboard/')
            ->action(static fn(): Response => new Response('matched'))
            ->prefix($routePrefix)
            ->methods(...$routeMethods)
            ->types(...$routeTypes);

        if (!$matches) {
            $this->expectException(RouteNotFoundException::class);
        }

        $response = $router->dispatch();

        if ($matches) {
            $this->assertSame('matched', $response->content());
        }
    }

    /**
     * @return array<string, array{string, ?string, string, list<string>, list<string>, bool}>
     */
    public static function requirementsProvider(): array
    {
        return [
            'matching method and prefix' => ['GET', null, '/admin', ['GET'], ['HTTP'], true],
            'HEAD is equivalent to GET'  => ['HEAD', null, '/admin', ['GET'], ['HTTP'], true],
            'method mismatch'            => ['POST', null, '/admin', ['GET'], ['HTTP'], false],
            'matching XHR type'          => ['GET', 'XMLHttpRequest', '/admin', ['GET'], ['XHR'], true],
            'type mismatch'              => ['GET', null, '/admin', ['GET'], ['XHR'], false],
            'prefix mismatch'            => ['GET', null, '/other', ['GET'], ['HTTP'], false],
        ];
    }

    private function router(string $path, string $method = 'GET', ?string $requestedWith = null): Router
    {
        $server = [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI'    => $path,
            'SERVER_NAME'    => 'example.test',
        ];

        if ($requestedWith !== null) {
            $server['HTTP_X_REQUESTED_WITH'] = $requestedWith;
        }

        return new Router(
            new Container(),
            new Request([], [], [], [], $server),
            new EventDispatcher(),
        );
    }
}
