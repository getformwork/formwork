<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use ErrorException;
use Formwork\Cms\App;
use Formwork\Http\JsonResponse;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Controllers\ErrorsController;
use Formwork\Parsers\Json;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\BuildsPanelControllers;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use RuntimeException;
use Throwable;

#[CoversClass(ErrorsController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class ErrorsControllerTest extends TestCase
{
    use BuildsPanelControllers;

    private int $outputBufferLevel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputBufferLevel = ob_get_level();
    }

    protected function tearDown(): void
    {
        $this->restoreOutputBuffers();

        $this->closePanelSession();
        parent::tearDown();
    }

    public function testForbiddenHasItsStatus(): void
    {
        $response = $this->controller()->forbidden();

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    #[DataProvider('errorStatusProvider')]
    public function testErrorUsesTheGivenStatus(ResponseStatus $status): void
    {
        $response = $this->controller()->error($status);

        $this->assertSame($status, $response->status());
    }

    /**
     * @return iterable<string, array{ResponseStatus}>
     */
    public static function errorStatusProvider(): iterable
    {
        yield 'forbidden' => [ResponseStatus::Forbidden];
        yield 'internal server error' => [ResponseStatus::InternalServerError];
        yield 'bad gateway' => [ResponseStatus::BadGateway];
        yield 'service unavailable' => [ResponseStatus::ServiceUnavailable];
    }

    public function testErrorDefaultsToAnInternalServerError(): void
    {
        $this->assertSame(ResponseStatus::InternalServerError, $this->controller()->error()->status());
    }

    public function testThrowablesAreLogged(): void
    {
        $this->expectErrorLog();

        $response = $this->controller()->internalServerError(new RuntimeException('Boom'));

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
    }

    public function testResponsesWithoutAThrowableAreNotLogged(): void
    {
        // A logged error would be written to the output of the test, which is not allowed
        $this->assertSame(ResponseStatus::NotFound, $this->controller()->notFound()->status());
    }

    public function testRequestsFromScriptsGetAJsonResponse(): void
    {
        $response = $this->controller(['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'])->notFound();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::NotFound, $response->status());
        $this->assertSame('error', Json::parse($response->content())['status']);
    }

    public function testJsonResponsesOfLocalRequestsIncludeTheMessageOfTheThrowable(): void
    {
        $this->expectErrorLog();

        $response = $this->controller(['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'REMOTE_ADDR' => '127.0.0.1'])->internalServerError(new RuntimeException('Boom'));

        $this->assertStringContainsString('Boom', (string) Json::parse($response->content())['message']);
    }

    public function testJsonResponsesOfRemoteRequestsDoNotLeakTheMessageOfTheThrowable(): void
    {
        $this->expectErrorLog();

        $response = $this->controller(['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'])->internalServerError(new RuntimeException('Boom'));

        $this->assertStringNotContainsString('Boom', (string) Json::parse($response->content())['message']);
    }

    public function testTracesAreEmptyWithoutAThrowable(): void
    {
        $this->assertSame([], $this->trace(null));
    }

    public function testTracesBeginWithTheOriginOfTheThrowable(): void
    {
        $runtimeException = new RuntimeException('Boom');

        $trace = $this->trace($runtimeException);

        $this->assertSame(['file' => $runtimeException->getFile(), 'line' => $runtimeException->getLine()], $trace[0]);
        $this->assertSame($runtimeException->getTrace(), array_slice($trace, 1));
    }

    public function testTracesOfErrorsBeginWithTheirOriginInsteadOfTheErrorHandler(): void
    {
        // Errors are converted to exceptions by the error handler of the application
        $displayErrors = ini_get('display_errors');
        $errorReporting = error_reporting(E_ALL);
        (new ReflectionMethod(App::class, 'loadErrorHandler'))->invoke(App::instance());

        try {
            $errorException = $this->captureException(ErrorException::class, static fn(): bool => trigger_error('Warning', E_USER_WARNING));
        } finally {
            restore_error_handler();
            error_reporting($errorReporting);
            ini_set('display_errors', (string) $displayErrors);
        }

        $trace = $this->trace($errorException);

        // The frame of the error handler is removed, so the trace begins with the function that raised the error, which is not repeated
        $this->assertSame(array_slice($errorException->getTrace(), 1), $trace);
        $this->assertSame([$errorException->getFile(), $errorException->getLine()], [$trace[0]['file'] ?? null, $trace[0]['line'] ?? null]);
    }

    /**
     * @param array<string, string> $server
     * @param array<string, mixed>  $system
     */
    private function controller(array $server = [], array $system = []): ErrorsController
    {
        $this->restoreOutputBuffers();

        return $this->makeController(ErrorsController::class, system: $system, realErrorsController: true, server: $server);
    }

    /**
     * @return array<mixed>
     */
    private function trace(?Throwable $throwable): array
    {
        $controller = $this->controller();

        /** @var array<mixed> */
        return (new ReflectionMethod($controller, 'getTrace'))->invoke($controller, $throwable);
    }

    /**
     * Error responses clean all the output buffers, including the ones of PHPUnit
     */
    private function restoreOutputBuffers(): void
    {
        while (ob_get_level() < $this->outputBufferLevel) {
            ob_start();
        }
    }
}
