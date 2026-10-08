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

    #[DataProvider('filterRequirementsProvider')]
    public function testFiltersApplyOnlyWhenMethodTypeAndPrefixMatch(
        string $method,
        ?string $requestedWith,
        string $prefix,
        array $filterMethods,
        array $filterTypes,
        bool $applies,
    ): void {
        $router = $this->router('/admin/page/', $method, $requestedWith);
        $router->addFilter('filter', static fn(): Response => new Response('filtered'))
            ->prefix($prefix)
            ->methods(...$filterMethods)
            ->types(...$filterTypes);
        $router->addRoute('page', '/admin/page/')
            ->action(static fn(): Response => new Response('route'))
            ->methods('GET', 'POST')
            ->types('HTTP', 'XHR');

        $this->assertSame($applies ? 'filtered' : 'route', $router->dispatch()->content());
    }

    /**
     * @return iterable<string, array{string, ?string, string, list<string>, list<string>, bool}>
     */
    public static function filterRequirementsProvider(): iterable
    {
        yield 'everything matches' => ['GET', null, '/admin', ['GET'], ['HTTP'], true];
        yield 'method mismatch' => ['POST', null, '/admin', ['GET'], ['HTTP'], false];
        yield 'type mismatch' => ['GET', 'XMLHttpRequest', '/admin', ['GET'], ['HTTP'], false];
        yield 'prefix mismatch' => ['GET', null, '/other', ['GET'], ['HTTP'], false];
        yield 'HEAD is handled as GET' => ['HEAD', null, '/admin', ['GET'], ['HTTP'], true];
        yield 'any of several methods' => ['POST', null, '/admin', ['PUT', 'POST'], ['HTTP'], true];
        yield 'without prefix' => ['GET', null, '', ['GET'], ['HTTP'], true];
    }

    public function testFiltersAreRunInRegistrationOrderUntilOneReturnsAResponse(): void
    {
        $calls = [];
        $router = $this->router('/');
        $router->addFilter('first', static function () use (&$calls): null {
            $calls[] = 'first';
            return null;
        });
        $router->addFilter('second', static function () use (&$calls): Response {
            $calls[] = 'second';
            return new Response('second');
        });
        $router->addFilter('third', static function () use (&$calls): Response {
            $calls[] = 'third';
            return new Response('third');
        });
        $router->addRoute('home', '/')->action(static fn(): Response => new Response('route'));

        $this->assertSame('second', $router->dispatch()->content());
        $this->assertSame(['first', 'second'], $calls);
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

    public function testNoCurrentRouteOrParamsRemainWhenARouteIsRejectedByItsConstraints(): void
    {
        $router = $this->router('/users/41/');
        $router->addRoute('user', '/users/{id}')
            ->action(static fn(): Response => new Response('matched'))
            ->where('id', ['42']);

        try {
            $router->dispatch();
            $this->fail('The route should have been rejected by its constraint.');
        } catch (RouteNotFoundException) {
            // Expected
        }

        $this->assertNull($router->current());
        $this->assertSame([], $router->params()->toArray());
    }

    public function testRouteParamsAreOnlyTheOnesOfTheMatchedRoute(): void
    {
        $router = $this->router('/posts/7/');
        $router->addRoute('rejected', '/posts/{slug:alpha}')->action(static fn(): Response => new Response('no'));
        $router->addRoute('matched', '/posts/{id:digits}')->action(static fn(): Response => new Response('yes'));

        $router->dispatch();

        $this->assertSame(['id' => '7'], $router->params()->toArray());
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

    public function testGenerateIgnoresUnknownParametersAndCastsScalarValues(): void
    {
        $router = $this->router('/');
        $router->addRoute('user', '/users/{id:digits}');

        $this->assertSame('/users/42/', $router->generate('user', ['id' => 42, 'unused' => 'ignored']));
    }

    public function testGenerateOmitsOptionalParametersAndTheirSeparators(): void
    {
        $router = $this->router('/');
        $router->addRoute('archive', '/archive/{year:digits}?/{month:digits}?');

        $this->assertSame('/archive/', $router->generate('archive', []));
        $this->assertSame('/archive/2025/', $router->generate('archive', ['year' => '2025']));
        $this->assertSame('/archive/2025/10/', $router->generate('archive', ['year' => '2025', 'month' => '10']));
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

    public function testLoadFromFileKeepsPrefixesDeclaredByEachEntryOverTheGivenOne(): void
    {
        $router = $this->router('/');
        $router->loadFromFile(__DIR__ . '/Fixtures/routes.php', prefix: '/ignored');

        $this->assertSame('/api', $router->routes()->get('loaded')->getPrefix());
        $this->assertSame('/api', $router->filters()->get('loaded-filter')->getPrefix());
    }

    public function testLoadFromFileAppliesTheGivenPrefixToEntriesWithoutOne(): void
    {
        $router = $this->router('/');
        $router->loadFromFile(__DIR__ . '/Fixtures/routes.php', prefix: '/panel');

        $this->assertSame('/panel', $router->routes()->get('unprefixed')->getPrefix());
        $this->assertSame('/panel', $router->filters()->get('unprefixed-filter')->getPrefix());
    }

    public function testLoadFromFileLeavesEntriesWithoutPrefixUnprefixedByDefault(): void
    {
        $router = $this->router('/');
        $router->loadFromFile(__DIR__ . '/Fixtures/routes.php');

        $this->assertSame('', $router->routes()->get('unprefixed')->getPrefix());
        $this->assertSame('', $router->filters()->get('unprefixed-filter')->getPrefix());
    }

    public function testLoadFromFileAppliesPathsMethodsTypesAndConstraints(): void
    {
        $router = $this->router('/');
        $router->loadFromFile(__DIR__ . '/Fixtures/routes.php');

        $loaded = $router->routes()->get('loaded');
        $unprefixed = $router->routes()->get('unprefixed');
        $filter = $router->filters()->get('unprefixed-filter');

        $this->assertSame('/loaded/{id:digits}', $loaded->getPath());
        $this->assertSame(['POST'], $loaded->getMethods());
        $this->assertSame(['HTTP'], $loaded->getTypes());
        $this->assertSame(['id' => ['42']], $loaded->getConstraints());
        $this->assertSame(['GET', 'POST'], $unprefixed->getMethods());
        $this->assertSame(['XHR'], $unprefixed->getTypes());
        $this->assertSame([], $unprefixed->getConstraints());
        $this->assertSame(['POST', 'PUT'], $filter->getMethods());
        $this->assertSame(['XHR'], $filter->getTypes());
    }

    public function testLoadFromFileMergesActionParametersGivingPriorityToTheOnesOfTheRoute(): void
    {
        $router = $this->router('/');
        $router->loadFromFile(
            __DIR__ . '/Fixtures/routes.php',
            actionParameters: ['fromGlobal' => 'global', 'shared' => 'global'],
        );

        $this->assertSame(
            ['fromRouteFile' => 'route', 'shared' => 'route', 'fromGlobal' => 'global'],
            $router->routes()->get('loaded')->getActionParameters(),
        );
        $this->assertSame(
            ['fromGlobal' => 'global', 'shared' => 'global'],
            $router->routes()->get('unprefixed')->getActionParameters(),
        );
    }

    public function testLoadedRoutesAreDispatchedHonouringTheirConstraintsAndPrefixes(): void
    {
        $router = $this->router('/api/loaded/42/', 'POST');
        $router->loadFromFile(__DIR__ . '/Fixtures/routes.php');

        $this->assertSame('route', $router->dispatch()->content());
        $this->assertSame(['id' => '42'], $router->params()->toArray());

        $router = $this->router('/api/loaded/41/', 'POST');
        $router->loadFromFile(__DIR__ . '/Fixtures/routes.php');

        $this->expectException(RouteNotFoundException::class);
        $router->dispatch();
    }

    public function testLoadedXhrRoutesIgnoreRegularHttpRequests(): void
    {
        $router = $this->router('/unprefixed/', 'GET');
        $router->loadFromFile(__DIR__ . '/Fixtures/routes.php');

        $this->expectException(RouteNotFoundException::class);
        $router->dispatch();
    }

    public function testLoadedXhrRoutesMatchXmlHttpRequests(): void
    {
        $router = $this->router('/unprefixed/', 'GET', 'XMLHttpRequest');
        $router->loadFromFile(__DIR__ . '/Fixtures/routes.php');

        $router->dispatch();

        $this->assertSame('unprefixed', $router->current()?->getName());
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
