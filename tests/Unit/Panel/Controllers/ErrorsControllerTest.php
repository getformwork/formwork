<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Http\JsonResponse;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Controllers\ErrorsController;
use Formwork\Parsers\Json;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\Fixtures\BuildsPanelControllers;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

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

    public function testNotFoundRendersTheErrorView(): void
    {
        $response = $this->controller()->notFound();

        $this->assertSame(ResponseStatus::NotFound, $response->status());

        $view = $this->renderedView($response);

        $this->assertSame('@panel.errors.error', $view['view']);
        $this->assertSame(404, $view['vars']['code']);
        $this->assertSame('Return to dashboard', $view['vars']['action']['label']);
    }

    public function testForbiddenRendersTheErrorView(): void
    {
        $response = $this->controller()->forbidden();

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
        $this->assertSame(403, $this->renderedView($response)['vars']['code']);
    }

    public function testInternalServerErrorRendersTheErrorView(): void
    {
        $this->expectErrorLog();

        $response = $this->controller()->internalServerError(new RuntimeException('Boom'));

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
        $this->assertSame(500, $this->renderedView($response)['vars']['code']);
    }

    #[DataProvider('statusProvider')]
    public function testErrorUsesTheGivenStatus(ResponseStatus $status): void
    {
        $response = $this->controller()->error($status);

        $this->assertSame($status, $response->status());
        $this->assertSame($status->code(), $this->renderedView($response)['vars']['code']);
    }

    /**
     * @return iterable<string, array{ResponseStatus}>
     */
    public static function statusProvider(): iterable
    {
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

        $this->controller()->internalServerError(new RuntimeException('Boom'));
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

        $this->assertStringContainsString('Boom', Json::parse($response->content())['message']);
    }

    public function testJsonResponsesOfRemoteRequestsDoNotLeakTheMessageOfTheThrowable(): void
    {
        $this->expectErrorLog();

        $response = $this->controller(['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'])->internalServerError(new RuntimeException('Boom'));

        $this->assertStringNotContainsString('Boom', Json::parse($response->content())['message']);
    }

    public function testErrorsOfLocalRequestsExposeTheThrowable(): void
    {
        $this->expectErrorLog();

        $vars = $this->renderedView($this->controller(['REMOTE_ADDR' => '127.0.0.1'])->internalServerError(new RuntimeException('Boom')))['vars'];

        $this->assertSame(RuntimeException::class, $vars['throwable']);
    }

    public function testErrorsOfRemoteRequestsDoNotExposeTheThrowable(): void
    {
        $this->expectErrorLog();

        $vars = $this->renderedView($this->controller(['SERVER_NAME' => 'example.com', 'REMOTE_ADDR' => '203.0.113.7'])->internalServerError(new RuntimeException('Boom')))['vars'];

        $this->assertArrayNotHasKey('throwable', $vars);
    }

    public function testDebugModeExposesTheThrowableToRemoteRequests(): void
    {
        $this->expectErrorLog();

        $controller = $this->controller(['SERVER_NAME' => 'example.com'], ['debug' => ['enabled' => true]]);

        $vars = $this->renderedView($controller->internalServerError(new RuntimeException('Boom')))['vars'];

        $this->assertSame(RuntimeException::class, $vars['throwable']);
    }

    public function testErrorsOfTheServerHaveAnActionToReportThem(): void
    {
        $this->assertStringStartsWith('https://github.com/getformwork/formwork/issues', $this->renderedView($this->controller()->error())['vars']['action']['href']);
    }

    /**
     * @param array<string, string> $server
     * @param array<string, mixed>  $system
     */
    private function controller(array $server = [], array $system = []): ErrorsController
    {
        $this->restoreOutputBuffers();

        return $this->makeController(ErrorsController::class, [], $system, server: $server, realErrorsController: true);
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
