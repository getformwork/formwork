<?php

namespace Formwork\Tests\Unit\Router;

use Formwork\Events\EventDispatcher;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Router\Exceptions\InvalidRouteException;
use Formwork\Router\Exceptions\RouteNotFoundException;
use Formwork\Router\RouteParams;
use Formwork\Router\Router;
use Formwork\Services\Container;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

#[CoversClass(Router::class)]
final class RouterTest extends TestCase
{
    public function testConstructorNormalizesRequestAndInitializesState(): void
    {
        $router = $this->router('https://example.test/docs?draft=1#section');

        $this->assertSame('/docs/', $router->request());
        $this->assertNull($router->current());
        $this->assertSame([], $router->params()->toArray());
        $this->assertTrue($router->routes()->isEmpty());
        $this->assertTrue($router->filters()->isEmpty());
    }

    public function testRequestPrefixAndSetRequestUseNormalizedPaths(): void
    {
        $router = $this->router('/admin/users');

        $this->assertTrue($router->requestHasPrefix('/admin'));
        $this->assertFalse($router->requestHasPrefix('/administrator'));

        $router->setRequest('/public/index?tab=1');

        $this->assertSame('/public/index/', $router->request());
        $this->assertTrue($router->requestHasPrefix('public'));
        $this->assertFalse($router->requestHasPrefix('/admin'));
    }

    public function testSetRequestRejectsAUriWithoutAPath(): void
    {
        $router = $this->router('/');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URI "https://"');
        $router->setRequest('https://');
    }

    public function testAddRouteAndFilterReturnConfiguredObjectsAndCollections(): void
    {
        $router = $this->router('/');
        $route = $router->addRoute('home', '/')->action(static fn(): Response => new Response('home'));
        $filter = $router->addFilter('auth', static fn(): null => null);

        $this->assertSame($route, $router->routes()->get('home'));
        $this->assertSame($filter, $router->filters()->get('auth'));
        $this->assertCount(1, $router->routes());
        $this->assertCount(1, $router->filters());
    }

    public function testDispatchesAClosureActionAndInjectsRouteParams(): void
    {
        $router = $this->router('/users/42/');
        $route = $router->addRoute('user', '/users/{id:digits}')
            ->action(static fn(RouteParams $params): Response => new Response($params->get('id')));

        $response = $router->dispatch();

        $this->assertSame('42', $response->content());
        $this->assertSame($route, $router->current());
        $this->assertSame(['id' => '42'], $router->params()->toArray());
    }

    public function testDispatchesTheFirstMatchingRouteAndSkipsNonMatchingRoutes(): void
    {
        $router = $this->router('/posts/42/');
        $router->addRoute('wrong', '/users/{id}')->action(static fn(): Response => new Response('wrong'));
        $matching = $router->addRoute('post', '/posts/{id}')
            ->action(static fn(): Response => new Response('post'));
        $router->addRoute('unreached', '/posts/{id}')->action(static fn(): Response => new Response('unreached'));

        $this->assertSame('post', $router->dispatch()->content());
        $this->assertSame($matching, $router->current());
    }

    public function testDispatchesFilterResponseBeforeRoutes(): void
    {
        $router = $this->router('/admin/');
        $router->addFilter('maintenance', static fn(): Response => new Response('filtered'));
        $router->addRoute('home', '/admin/')->action(static fn(): Response => new Response('route'));

        $this->assertSame('filtered', $router->dispatch()->content());
        $this->assertNull($router->current());
    }

    public function testAFilterReturningNullAllowsRouteDispatchToContinue(): void
    {
        $router = $this->router('/');
        $router->addFilter('observer', static fn(): null => null);
        $router->addRoute('home', '/')->action(static fn(): Response => new Response('route'));

        $this->assertSame('route', $router->dispatch()->content());
    }

    public function testFilterRequirementsAreAppliedBeforeItsAction(): void
    {
        $router = $this->router('/admin/', 'POST');
        $router->addFilter('get-only', static fn(): Response => new Response('wrong'))->methods('GET');
        $router->addFilter('other-prefix', static fn(): Response => new Response('wrong'))->prefix('/other');
        $router->addRoute('home', '/admin/')->action(static fn(): Response => new Response('route'))->methods('POST');

        $this->assertSame('route', $router->dispatch()->content());
    }

    public function testDispatchRejectsInvalidActions(): void
    {
        $router = $this->router('/');
        $router->addRoute('home', '/')->action('not-callable');

        $this->expectException(InvalidRouteException::class);
        $this->expectExceptionMessage('Invalid callback');
        $router->dispatch();
    }

    public function testDispatchThrowsWhenNoRouteMatches(): void
    {
        $router = $this->router('/missing');

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('No route matches with "/missing/"');
        $router->dispatch();
    }

    public function testDispatchSupportsControllerActionsAndActionParameters(): void
    {
        $router = $this->router('/controller/');
        $router->addRoute('controller', '/controller/')
            ->action(RouteControllerFixture::class . '@handle')
            ->actionParameters(['fromRoute' => 'direct']);

        $this->assertSame('direct', $router->dispatch()->content());
    }

    public function testRouteActionResolvedEventCanReplaceTheAction(): void
    {
        $events = new EventDispatcher();
        $router = $this->router('/', events: $events);
        $router->addRoute('home', '/')->action(static fn(): Response => new Response('original'));
        $events->on('routeActionResolved', static function ($event): void {
            $event->setAction(static fn(): Response => new Response('replaced'));
        });

        $this->assertSame('replaced', $router->dispatch()->content());
    }

    #[DataProvider('generateProvider')]
    public function testGenerateBuildsPathsForSupportedParameterPatterns(
        string $path,
        array $params,
        string $expected,
    ): void {
        $router = $this->router('/');
        $router->addRoute('generated', $path);

        $this->assertSame($expected, $router->generate('generated', $params));
    }

    /**
     * @return array<string, array{string, array<string, string>, string}>
     */
    public static function generateProvider(): array
    {
        return [
            'static path'         => ['/docs/', [], '/docs/'],
            'default any pattern' => ['/users/{id}', ['id' => 'abc-123'], '/users/abc-123/'],
            'all pattern'         => ['/files/{path:all}', ['path' => 'a/b.txt'], '/files/a/b.txt/'],
            'slug shortcut'       => ['/posts/{slug:slug}', ['slug' => 'hello-world'], '/posts/hello-world/'],
            'alnum shortcut'      => ['/items/{code:alnum}', ['code' => 'A123'], '/items/A123/'],
            'alpha shortcut'      => ['/letters/{value:alpha}', ['value' => 'abc'], '/letters/abc/'],
            'digits shortcut'     => ['/page/{number:digits}', ['number' => '12'], '/page/12/'],
            'hex shortcut'        => ['/hash/{value:xdigits}', ['value' => 'deadbeef'], '/hash/deadbeef/'],
            'number shortcut'     => ['/id/{number:number}', ['number' => '12'], '/id/12/'],
            'base64 shortcut'     => ['/token/{value:base64}', ['value' => 'YWJj'], '/token/YWJj/'],
            'optional parameter'  => ['/archive/{year:digits}?', [], '/archive/'],
            'comma separator'     => ['/search,{term}', ['term' => 'php'], '/search,php/'],
            'colon separator'     => ['/version:{version}', ['version' => 'v2'], '/version:v2/'],
        ];
    }

    public function testGenerateUsesPrefixAndRejectsUnknownRoutes(): void
    {
        $router = $this->router('/');
        $router->addRoute('admin', '/dashboard/')->prefix('/admin');

        $this->assertSame('/admin/dashboard/', $router->generate('admin', []));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route "missing" does not exist');
        $router->generate('missing', []);
    }

    public function testGenerateRejectsMissingAndInvalidRequiredParameters(): void
    {
        $router = $this->router('/');
        $router->addRoute('user', '/users/{id:digits}');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Non-optional parameter "id" requires a value to generate route');
        $router->generate('user', []);
    }

    public function testGenerateRejectsValuesThatDoNotMatchTheirConstraint(): void
    {
        $router = $this->router('/');
        $router->addRoute('user', '/users/{id:digits}');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for param "id"');
        $router->generate('user', ['id' => 'abc']);
    }

    public function testGenerateWithUsesCurrentParametersButExplicitValuesWin(): void
    {
        $router = $this->router('/users/42/overview/');
        $router->addRoute('user', '/users/{id}/{tab}?')->action(
            static fn(RouteParams $params): Response => new Response($params->get('id'))
        );
        $router->dispatch();

        $this->assertSame('/users/99/overview/', $router->generateWith('user', ['id' => '99']));
    }

    public function testRewriteRequiresADispatchedCurrentRoute(): void
    {
        $router = $this->router('/');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot rewrite current route: router has not dispatched the request yet');
        $router->rewrite([]);
    }

    public function testRewriteUsesCurrentRouteAndCurrentParameters(): void
    {
        $router = $this->router('/users/42/');
        $router->addRoute('user', '/users/{id}')->action(static fn(): Response => new Response('ok'));
        $router->dispatch();

        $this->assertSame('/users/84/', $router->rewrite(['id' => '84']));
    }

    public function testInvalidRouteDefinitionsExposeMeaningfulCompilationErrors(): void
    {
        $router = $this->router('/users/42/');
        $router->addRoute('invalid-separator', '/users{id}')->action(static fn(): Response => new Response('no'));

        $this->expectException(InvalidRouteException::class);
        $this->expectExceptionMessage('Parameter "id" must be preceded by a separator');
        $router->dispatch();
    }

    public function testDuplicateRouteParametersAreRejected(): void
    {
        $router = $this->router('/users/42/42/');
        $router->addRoute('duplicate', '/users/{id}/{id}')->action(static fn(): Response => new Response('no'));

        $this->expectException(InvalidRouteException::class);
        $this->expectExceptionMessage('Parameter "id" cannot be used more than once');
        $router->dispatch();
    }

    public function testLoadFromFileAppliesRoutesFiltersPrefixesAndActionParameters(): void
    {
        $router = $this->router('/api/loaded/42/', 'POST');
        $router->loadFromFile(
            __DIR__ . '/Fixtures/routes.php',
            prefix: '/ignored',
            actionParameters: ['fromGlobal' => 'global'],
        );

        $route = $router->routes()->get('loaded');
        $filter = $router->filters()->get('loaded-filter');

        $this->assertSame('/api', $route->getPrefix());
        $this->assertSame(['fromRouteFile' => 'route', 'fromGlobal' => 'global'], $route->getActionParameters());
        $this->assertSame(['POST'], $route->getMethods());
        $this->assertSame('/api', $filter->getPrefix());
        $this->assertSame('route', $router->dispatch()->content());
    }

    private function router(
        string $path,
        string $method = 'GET',
        ?string $requestedWith = null,
        ?EventDispatcher $events = null,
    ): Router {
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
            $events ?? new EventDispatcher(),
        );
    }
}

final class RouteControllerFixture
{
    public function __construct(
        private string $fromRoute = '',
        private string $fromRouteFile = '',
    ) {}

    public function handle(string $fromRouteFile = ''): Response
    {
        return new Response($this->fromRoute . $this->fromRouteFile . $fromRouteFile);
    }
}

final class RouteFilterFixture
{
    public function handle(): Response
    {
        return new Response('filter');
    }
}
