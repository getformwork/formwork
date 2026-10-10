<?php

namespace Formwork\Tests\Unit\Controllers;

use Formwork\Cms\App;
use Formwork\Controllers\AbstractController;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Router\Router;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Controllers\Fixtures\BuildsControllers;
use Formwork\Tests\Unit\Controllers\Fixtures\ExposedController;
use Formwork\Tests\Unit\Controllers\Fixtures\ForwardTarget;
use Formwork\Utils\FileSystem;
use Formwork\View\ViewFactory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

#[CoversClass(AbstractController::class)]
final class AbstractControllerTest extends TestCase
{
    use BuildsControllers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        FileSystem::createDirectory(TESTS_TMP_PATH . '/views');
        FileSystem::write(TESTS_TMP_PATH . '/views/greeting.php', 'Hello <?= $this->escape($name) ?>');
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testNameIsDerivedFromTheClassName(): void
    {
        $this->assertSame('exposed', $this->controller()->name());
    }

    public function testViewsAreRenderedThroughTheViewFactory(): void
    {
        $factory = new ViewFactory(['escape' => static fn(string $text): string => htmlspecialchars($text)], ['test' => TESTS_TMP_PATH . '/views'], App::instance());

        $controller = $this->controller(services: [ViewFactory::class => $factory]);

        $this->assertSame('Hello &lt;World&gt;', $controller->render('@test.greeting', ['name' => '<World>']));
    }

    // Redirects

    public function testRedirectsGoThroughTheApplicationUri(): void
    {
        $response = $this->controller()->redirectTo('/panel/login/');

        $this->assertSame(ResponseStatus::Found, $response->status());
        $this->assertSame(App::instance()->uri()->path('/panel/login/'), $response->headers()->get('Location'));
    }

    public function testRedirectStatusAndHeadersCanBeChosen(): void
    {
        $response = $this->controller()->redirectTo('/moved/', ResponseStatus::MovedPermanently, ['X-Custom' => 'value']);

        $this->assertSame(ResponseStatus::MovedPermanently, $response->status());
        $this->assertSame('value', $response->headers()->get('X-Custom'));
    }

    public function testRedirectToRefererReturnsToTheSameSitePage(): void
    {
        $response = $this->controller(server: ['REQUEST_URI' => '/current/', 'HTTP_REFERER' => 'http://localhost/previous/'])->redirectBack();

        $this->assertSame('http://localhost/previous/', $response->headers()->get('Location'));
    }

    public function testRedirectToRefererFallsBackToTheDefaultWithoutReferer(): void
    {
        $response = $this->controller(server: ['REQUEST_URI' => '/current/'])->redirectBack(default: '/home/');

        $this->assertSame(App::instance()->uri()->path('/home/'), $response->headers()->get('Location'));
    }

    public function testRedirectToRefererDoesNotLoopOnTheCurrentPage(): void
    {
        $response = $this->controller(server: ['SCRIPT_NAME' => '/site/index.php', 'REQUEST_URI' => '/site/current/', 'HTTP_REFERER' => 'http://localhost/site/current/'])->redirectBack(default: '/home/');

        $this->assertSame(App::instance()->uri()->path('/home/'), $response->headers()->get('Location'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function foreignReferers(): iterable
    {
        yield 'other host' => ['https://evil.example/previous/'];
        yield 'other scheme' => ['https://localhost/previous/'];
        yield 'host as prefix of another host' => ['http://localhost.evil.example/previous/'];
        yield 'host with userinfo' => ['http://localhost@evil.example/previous/'];
        yield 'protocol relative' => ['//evil.example/previous/'];
        yield 'javascript scheme' => ['javascript:alert(1)'];
        yield 'data scheme' => ['data:text/html,<script>alert(1)</script>'];
        yield 'other port' => ['http://localhost:8080/previous/'];
        yield 'relative path' => ['/previous/'];
        yield 'garbage' => ['not a url'];
    }

    #[DataProvider('foreignReferers')]
    public function testRedirectToRefererNeverLeavesTheSite(string $referer): void
    {
        $response = $this->controller(server: ['REQUEST_URI' => '/current/', 'HTTP_REFERER' => $referer])->redirectBack(default: '/home/');

        $location = (string) $response->headers()->get('Location');
        $this->assertSame(App::instance()->uri()->path('/home/'), $location, sprintf('Referer "%s" was trusted', $referer));
    }

    public function testRedirectToRefererHonoursTheBasePath(): void
    {
        $inside = $this->controller(server: ['REQUEST_URI' => '/panel/current/', 'HTTP_REFERER' => 'http://localhost/panel/previous/'])->redirectBack(default: '/home/', base: '/panel/');
        $outside = $this->controller(server: ['REQUEST_URI' => '/panel/current/', 'HTTP_REFERER' => 'http://localhost/other/'])->redirectBack(default: '/home/', base: '/panel/');

        $this->assertSame('http://localhost/panel/previous/', $inside->headers()->get('Location'));
        $this->assertSame(App::instance()->uri()->path('/home/'), $outside->headers()->get('Location'));
    }

    public function testRedirectToRefererKeepsStatusAndHeaders(): void
    {
        $response = $this->controller(server: ['REQUEST_URI' => '/current/', 'HTTP_REFERER' => 'http://localhost/previous/'])->redirectBack(ResponseStatus::SeeOther, ['X-Custom' => 'value']);

        $this->assertSame(ResponseStatus::SeeOther, $response->status());
        $this->assertSame('value', $response->headers()->get('X-Custom'));
    }

    public function testRedirectToRefererDoesNotLoopOnTheCurrentPageOfASiteInTheDocumentRoot(): void
    {
        $response = $this->controller(server: ['REQUEST_URI' => '/current/', 'HTTP_REFERER' => 'http://localhost/current/'])->redirectBack(default: '/home/');

        $this->assertSame(App::instance()->uri()->path('/home/'), $response->headers()->get('Location'));
    }

    // Forwarding

    public function testRequestsCanBeForwardedToAnotherController(): void
    {
        $response = $this->controller()->forwardTo(ForwardTarget::class, 'greet', ['name' => 'Ada']);

        $this->assertSame('Hello Ada from forwardtarget', $response->content());
    }

    public function testForwardedActionsReceiveTheirDefaults(): void
    {
        $this->assertSame('Hello nobody from forwardtarget', $this->controller()->forwardTo(ForwardTarget::class, 'greet')->content());
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function notControllers(): iterable
    {
        yield 'plain class' => [stdClass::class];
        yield 'response' => [Response::class];
        yield 'the abstract controller itself' => [AbstractController::class];
    }

    #[DataProvider('notControllers')]
    public function testOnlyControllersCanBeForwardedTo(string $class): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Controllers must extend');

        $this->controller()->forwardTo($class, 'anything');
    }

    public function testNonExistentClassesCannotBeForwardedTo(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->controller()->forwardTo('Nothing\Like\This', 'anything');
    }

    // Routes

    public function testRoutesAreGeneratedByTheRouter(): void
    {
        $router = $this->createMock(Router::class);
        $router->expects($this->once())->method('generate')->with('page', ['page' => 'about'])->willReturn('/about/');

        $this->assertSame('/about/', $this->controller(router: $router)->route('page', ['page' => 'about']));
    }

    /**
     * @param array<string, mixed>  $server
     * @param array<string, object> $services
     */
    private function controller(array $server = [], array $services = [], ?Router $router = null): ExposedController
    {
        return $this->makeController(ExposedController::class, server: $server, services: $services, router: $router);
    }
}
