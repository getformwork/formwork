<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

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

    public function testForbiddenHasItsStatus(): void
    {
        $response = $this->controller()->forbidden();

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testInternalServerErrorHasItsStatus(): void
    {
        $this->expectErrorLog();

        $response = $this->controller()->internalServerError(new RuntimeException('Boom'));

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
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
     * Error responses clean all the output buffers, including the ones of PHPUnit
     */
    private function restoreOutputBuffers(): void
    {
        while (ob_get_level() < $this->outputBufferLevel) {
            ob_start();
        }
    }
}
