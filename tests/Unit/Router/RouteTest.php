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

    #[DataProvider('patternProvider')]
    public function testParameterPatternsAcceptOnlyTheirOwnValues(string $pattern, string $value, bool $matches): void
    {
        $router = $this->router('/value/' . $value . '/');
        $router->addRoute('value', '/value/{param:' . $pattern . '}')
            ->action(static fn(RouteParams $params): Response => new Response($params->get('param')));

        if (!$matches) {
            $this->expectException(RouteNotFoundException::class);
        }

        $this->assertSame($value, $router->dispatch()->content());
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function patternProvider(): iterable
    {
        yield 'any accepts a segment' => ['any', 'some-value_1', true];
        yield 'any rejects nested segments' => ['any', 'a/b', false];
        yield 'all accepts nested segments' => ['all', 'a/b/c.txt', true];
        yield 'slug accepts hyphenated words' => ['slug', 'hello-world-2', true];
        yield 'slug rejects underscores' => ['slug', 'hello_world', false];
        yield 'slug rejects leading hyphens' => ['slug', '-hello', false];
        yield 'slug rejects consecutive hyphens' => ['slug', 'hello--world', false];
        yield 'alnum accepts letters and digits' => ['alnum', 'abc123', true];
        yield 'alnum rejects hyphens' => ['alnum', 'abc-123', false];
        yield 'alpha accepts letters' => ['alpha', 'abcXYZ', true];
        yield 'alpha rejects digits' => ['alpha', 'abc1', false];
        yield 'digits accepts leading zeros' => ['digits', '007', true];
        yield 'digits rejects letters' => ['digits', '12a', false];
        yield 'xdigits accepts hexadecimal digits' => ['xdigits', 'deadbeef09', true];
        yield 'xdigits rejects other letters' => ['xdigits', 'deadbeeg', false];
        yield 'number accepts positive integers' => ['number', '12', true];
        yield 'number rejects leading zeros' => ['number', '012', false];
        yield 'number rejects decimals' => ['number', '1.5', false];
        yield 'base64 accepts padding' => ['base64', 'YWJjZA==', true];
        yield 'base64 rejects excessive padding' => ['base64', 'YWJj===', false];
        yield 'base64 rejects spaces' => ['base64', 'YW Jj', false];
        yield 'custom regex' => ['[a-c]+', 'abcab', true];
        yield 'custom regex mismatch' => ['[a-c]+', 'abd', false];
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
            'prefix sharing only a stem' => ['GET', null, '/adm', ['GET'], ['HTTP'], false],
            'one of several methods'     => ['PUT', null, '/admin', ['POST', 'PUT'], ['HTTP'], true],
            'one of several types'       => ['GET', 'XMLHttpRequest', '/admin', ['GET'], ['HTTP', 'XHR'], true],
            'no allowed methods'         => ['GET', null, '/admin', [], ['HTTP'], false],
            'no allowed types'           => ['GET', null, '/admin', ['GET'], [], false],
            'route without the request prefix' => ['GET', null, '', ['GET'], ['HTTP'], false],
        ];
    }

    #[DataProvider('arrayConstraintProvider')]
    public function testArrayConstraintsAcceptOnlyTheListedValues(string $requestPath, bool $matches): void
    {
        $router = $this->router($requestPath);
        $route = $router->addRoute('user', '/users/{id}')
            ->action(static fn(): Response => new Response('matched'))
            ->where('id', ['42', '43']);

        if (!$matches) {
            $this->expectException(RouteNotFoundException::class);
        }

        $this->assertSame('matched', $router->dispatch()->content());
        $this->assertSame($route, $router->current());
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function arrayConstraintProvider(): iterable
    {
        yield 'first listed value' => ['/users/42/', true];
        yield 'second listed value' => ['/users/43/', true];
        yield 'unlisted value' => ['/users/41/', false];
        yield 'value containing a listed one' => ['/users/420/', false];
    }

    public function testClosureConstraintsReceiveTheParameterValue(): void
    {
        $received = [];
        $router = $this->router('/users/42/');
        $router->addRoute('user', '/users/{id}')
            ->action(static fn(): Response => new Response('matched'))
            ->where('id', static function (string $value) use (&$received): bool {
                $received[] = $value;
                return $value === '42';
            });

        $this->assertSame('matched', $router->dispatch()->content());
        $this->assertSame(['42'], $received);
    }

    public function testRejectedClosureConstraintsLetTheNextRouteMatch(): void
    {
        $router = $this->router('/users/41/');
        $router->addRoute('restricted', '/users/{id}')
            ->action(static fn(): Response => new Response('restricted'))
            ->where('id', static fn(string $value): bool => $value === '42');
        $fallback = $router->addRoute('fallback', '/users/{id}')
            ->action(static fn(): Response => new Response('fallback'));

        $this->assertSame('fallback', $router->dispatch()->content());
        $this->assertSame($fallback, $router->current());
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
