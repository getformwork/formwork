<?php

namespace Formwork\Tests\Unit\Controllers;

use Error;
use ErrorException;
use Exception;
use Formwork\Cms\App;
use Formwork\Controllers\ErrorsController;
use Formwork\Controllers\ErrorsControllerInterface;
use Formwork\Http\JsonResponse;
use Formwork\Http\ResponseStatus;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Controllers\Fixtures\BuildsControllers;
use Formwork\Utils\FileSystem;
use Formwork\View\ViewFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;
use RuntimeException;

#[CoversClass(ErrorsController::class)]
final class ErrorsControllerTest extends TestCase
{
    use BuildsControllers;

    private int $outputBufferLevel;

    private string|false $originalErrorLog;

    private string $errorLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();

        $this->outputBufferLevel = ob_get_level();

        // Error responses discard every active output buffer, including the ones used by the test runner
        $this->errorLog = TESTS_TMP_PATH . '/error.log';
        FileSystem::write($this->errorLog, '');
        $this->originalErrorLog = ini_get('error_log');
    }

    protected function tearDown(): void
    {
        ini_set('error_log', $this->originalErrorLog === false ? '' : $this->originalErrorLog);
        while (ob_get_level() < $this->outputBufferLevel) {
            ob_start();
        }
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testErrorsControllerCanBeUsedWhereTheErrorsInterfaceIsExpected(): void
    {
        $this->assertInstanceOf(ErrorsControllerInterface::class, $this->controller());
    }

    public function testNotFoundResponse(): void
    {
        $response = $this->controller()->notFound();

        $this->assertSame(ResponseStatus::NotFound, $response->status());
        $this->assertStringContainsString('Not Found', $response->content());
    }

    public function testForbiddenResponse(): void
    {
        $this->assertSame(ResponseStatus::Forbidden, $this->controller()->forbidden()->status());
    }

    public function testInternalServerErrorResponse(): void
    {
        $response = $this->controller()->internalServerError(new RuntimeException('boom'));

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
    }

    public function testStatusIsTakenFromTheArgument(): void
    {
        $this->assertSame(ResponseStatus::ServiceUnavailable, $this->controller()->error(ResponseStatus::ServiceUnavailable)->status());
    }

    public function testErrorDetailsAreNotRevealedToRemoteVisitors(): void
    {
        $exception = new RuntimeException('database password is hunter2');

        $response = $this->controller(debug: false)->internalServerError($exception);

        $this->assertStringNotContainsString('hunter2', $response->content());
        $this->assertStringNotContainsString(__FILE__, $response->content());
        $this->assertStringNotContainsString('RuntimeException', $response->content());
    }

    public function testErrorDetailsAreNotRevealedInJsonToRemoteVisitors(): void
    {
        $response = $this->controller(debug: false, xhr: true)->internalServerError(new RuntimeException('database password is hunter2'));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertStringNotContainsString('hunter2', $response->content());
    }

    public function testErrorDetailsAreShownInDebugMode(): void
    {
        $response = $this->controller(debug: true)->internalServerError(new RuntimeException('visible message'));

        $this->assertStringContainsString('visible message', $response->content());
    }

    public function testErrorDetailsAreShownToLocalhost(): void
    {
        $response = $this->controller(debug: false, ip: '127.0.0.1')->internalServerError(new RuntimeException('visible to localhost'));

        $this->assertStringContainsString('visible to localhost', $response->content());
    }

    public function testExceptionMessagesAreEscapedInTheDebugPage(): void
    {
        $response = $this->controller(debug: true)->internalServerError(new RuntimeException('<script>alert("xss")</script>'));

        $this->assertStringNotContainsString('<script>alert("xss")</script>', $response->content());
    }

    public function testAjaxRequestsReceiveJson(): void
    {
        $response = $this->controller(xhr: true)->notFound();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::NotFound, $response->status());
        $this->assertSame('error', json_decode($response->content(), true)['status']);
    }

    public function testAjaxDebugResponsesIncludeTheExceptionMessage(): void
    {
        $response = $this->controller(debug: true, xhr: true)->internalServerError(new RuntimeException('visible message'));

        $this->assertStringContainsString('visible message', $response->content());
    }

    public function testThrowablesAreLogged(): void
    {
        $this->controller()->internalServerError(new RuntimeException('logged message'));

        $log = FileSystem::read($this->errorLog);
        $this->assertStringContainsString('Uncaught RuntimeException: logged message', $log);
    }

    public function testNothingIsLoggedWithoutAThrowable(): void
    {
        $this->controller()->notFound();

        $this->assertSame('', FileSystem::read($this->errorLog));
    }

    public function testPendingOutputIsDiscarded(): void
    {
        echo 'partial output that must not be sent';
        ob_start();
        echo 'more output';

        $this->controller()->notFound();

        $this->assertSame(0, ob_get_level());
    }

    // Trace

    public function testTraceStartsWithTheOriginOfTheException(): void
    {
        $exception = new Exception('origin');

        $trace = $this->trace($exception);

        $this->assertSame($exception->getFile(), $trace[0]['file']);
        $this->assertSame($exception->getLine(), $trace[0]['line']);
        $this->assertArrayNotHasKey('function', $trace[0]);
        $this->assertCount(count($exception->getTrace()) + 1, $trace);
    }

    public function testTraceOfNullIsEmpty(): void
    {
        $this->assertSame([], $this->trace(null));
    }

    public function testErrorsWhoseFirstFrameIsTheOriginAreNotDuplicated(): void
    {
        $error = new ErrorException('warning', 0, E_WARNING, 'origin.php', 10);
        $frame = ['file' => 'origin.php', 'line' => 10, 'function' => 'strlen'];

        $trace = $this->trace($this->exceptionWithTrace($error, [$frame, ['file' => 'caller.php', 'line' => 3, 'function' => 'run']]));

        $this->assertSame([$frame, ['file' => 'caller.php', 'line' => 3, 'function' => 'run']], $trace);
    }

    public function testApplicationErrorHandlerFrameIsDropped(): void
    {
        $error = new ErrorException('warning', 0, E_WARNING, 'origin.php', 10);
        $handler = ['class' => App::class, 'function' => 'App->{closure}', 'type' => '->'];
        $frame = ['file' => 'origin.php', 'line' => 10, 'function' => 'strlen'];

        $trace = $this->trace($this->exceptionWithTrace($error, [$handler, $frame]));

        $this->assertSame([$frame], $trace);
    }

    /**
     * @param list<array<string, mixed>> $frames
     */
    private function exceptionWithTrace(ErrorException $exception, array $frames): ErrorException
    {
        (new \ReflectionProperty(Exception::class, 'trace'))->setValue($exception, $frames);
        return $exception;
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function trace(?\Throwable $throwable): array
    {
        $controller = $this->controller();

        return (new ReflectionMethod($controller, 'getTrace'))->invoke($controller, $throwable);
    }

    private function controller(bool $debug = false, bool $xhr = false, string $ip = '203.0.113.7'): ErrorsController
    {
        // PHPUnit points the error log to its own file while a test runs, so the redirection must happen inside the test
        ini_set('error_log', $this->errorLog);

        return $this->makeController(
            ErrorsController::class,
            ['debug' => ['enabled' => $debug]],
            server: ['REMOTE_ADDR' => $ip] + ($xhr ? ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'] : []),
            services: [ViewFactory::class => App::instance()->getService(ViewFactory::class)],
        );
    }
}
