<?php

namespace Formwork\Tests\Unit\Controllers;

use Formwork\Controllers\FilesController;
use Formwork\Http\FileResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Router\RouteParams;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Controllers\Fixtures\BuildsControllers;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

#[CoversClass(FilesController::class)]
final class FilesControllerTest extends TestCase
{
    use BuildsControllers;

    private string $filesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();

        $this->filesPath = $this->createDirectory(TESTS_TMP_PATH . '/site/files');
        FileSystem::write($this->filesPath . '/document.pdf', '%PDF-1.4 content');
        $this->createDirectory($this->filesPath . '/nested');
        FileSystem::write($this->filesPath . '/nested/inner.txt', 'inner');
        FileSystem::write(TESTS_TMP_PATH . '/site/config.yaml', 'secret: configuration');
        FileSystem::write(TESTS_TMP_PATH . '/site/secret.txt', 'outside of the files directory');
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testFilesAreServed(): void
    {
        $response = $this->controller()->file(new RouteParams(['name' => 'document.pdf']));

        $this->assertInstanceOf(FileResponse::class, $response);
        $this->assertSame($this->filesPath . '/document.pdf', $this->fileOf($response));
    }

    public function testMissingFilesFallBackToTheErrorPage(): void
    {
        $response = $this->controller()->file(new RouteParams(['name' => 'missing.pdf']));

        $this->assertSame(ResponseStatus::NotFound, $response->status());
        $this->assertSame('error page', $response->content());
    }

    public function testDirectoriesAreNotServed(): void
    {
        $this->assertSame(ResponseStatus::NotFound, $this->controller()->file(new RouteParams(['name' => 'nested']))->status());
        $this->assertSame(ResponseStatus::NotFound, $this->controller()->file(new RouteParams(['name' => '.']))->status());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function traversals(): iterable
    {
        yield 'parent directory' => ['../secret.txt'];
        yield 'nested parents' => ['nested/../../secret.txt'];
        yield 'configuration' => ['../config.yaml'];
        yield 'backslashes' => ['..\secret.txt'];
        yield 'many parents' => ['../../../../../../../../../../etc/hostname'];
        yield 'null byte' => ["document.pdf\0../secret.txt"];
    }

    #[DataProvider('traversals')]
    public function testFilesOutsideTheFilesDirectoryAreNeverServed(string $name): void
    {
        $response = $this->controller()->file(new RouteParams(['name' => $name]));

        $this->assertNotInstanceOf(FileResponse::class, $response, 'Served ' . ($response instanceof FileResponse ? $this->fileOf($response) : ''));
        $this->assertSame(ResponseStatus::NotFound, $response->status());
    }

    private function controller(): FilesController
    {
        return $this->makeController(FilesController::class, ['files' => ['paths' => ['site' => $this->filesPath]]]);
    }

    private function fileOf(Response $response): string
    {
        return (string) (new ReflectionProperty($response, 'path'))->getValue($response);
    }
}
