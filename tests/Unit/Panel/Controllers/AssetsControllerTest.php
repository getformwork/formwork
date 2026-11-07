<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Http\FileResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Controllers\AssetsController;
use Formwork\Router\RouteParams;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\BuildsPanelControllers;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

#[CoversClass(AssetsController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class AssetsControllerTest extends TestCase
{
    use BuildsPanelControllers;

    private string $assetsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assetsPath = TESTS_TMP_PATH . '/assets';

        FileSystem::createDirectory($this->assetsPath . '/js/chunks', recursive: true);
        FileSystem::createDirectory($this->assetsPath . '/css', recursive: true);
        FileSystem::createDirectory($this->assetsPath . '/icons', recursive: true);
        FileSystem::write($this->assetsPath . '/js/app.js', 'app');
        FileSystem::write($this->assetsPath . '/js/chunks/chunk-abc.js', 'chunk');
        FileSystem::write($this->assetsPath . '/css/panel.css', 'css');
        FileSystem::write($this->assetsPath . '/icons/home.svg', '<svg/>');

        $secretFile = TESTS_TMP_PATH . '/secret.txt';
        FileSystem::write($secretFile, 'secret');
    }

    protected function tearDown(): void
    {
        $this->closePanelSession();
        parent::tearDown();
    }

    public function testAssetsAreServedFromTheirTypeDirectory(): void
    {
        $response = $this->asset('css', 'panel.css');

        $this->assertInstanceOf(FileResponse::class, $response);
        $this->assertSame($this->assetsPath . '/css/panel.css', $this->responseFile($response));
    }

    public function testAssetsInSubdirectoriesAreServed(): void
    {
        $response = $this->asset('js', 'chunks/chunk-abc.js');

        $this->assertInstanceOf(FileResponse::class, $response);
        $this->assertSame($this->assetsPath . '/js/chunks/chunk-abc.js', $this->responseFile($response));
    }

    public function testMissingAssetsAreNotFound(): void
    {
        $response = $this->asset('css', 'missing.css');

        $this->assertNotInstanceOf(FileResponse::class, $response);
        $this->assertSame(ResponseStatus::NotFound, $response->status());
    }

    public function testDirectoriesAreNotServed(): void
    {
        $response = $this->asset('js', 'chunks');

        $this->assertNotInstanceOf(FileResponse::class, $response);
        $this->assertSame(ResponseStatus::NotFound, $response->status());
    }

    public function testAssetsOfAnotherTypeAreNotServed(): void
    {
        $response = $this->asset('css', 'app.js');

        $this->assertNotInstanceOf(FileResponse::class, $response);
    }

    #[DataProvider('escapingFileProvider')]
    public function testAssetsCannotLeaveTheAssetsDirectory(string $type, string $file): void
    {
        $response = $this->asset($type, $file);

        $this->assertNotInstanceOf(FileResponse::class, $response);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function escapingFileProvider(): iterable
    {
        yield 'parent directory in file' => ['css', '../../secret.txt'];
        yield 'deep traversal in file' => ['css', str_repeat('../', 20) . 'etc/passwd'];
        yield 'traversal after a directory' => ['js', 'chunks/../../../secret.txt'];
        yield 'absolute file' => ['css', '/etc/passwd'];
    }

    public function testResponsesAreNotCachedImmutablyByDefault(): void
    {
        $response = $this->asset('css', 'panel.css');

        $this->assertFalse($response->headers()->has('Cache-Control') && str_contains($response->headers()->get('Cache-Control'), 'immutable'));
    }

    public function testVersionedAssetsAreCachedImmutably(): void
    {
        $response = $this->asset('css', 'panel.css', ['v' => '1.2.3']);

        $this->assertSame('private, max-age=31536000, immutable', $response->headers()->get('Cache-Control'));
    }

    public function testIconsAreCachedImmutably(): void
    {
        $response = $this->asset('icons', 'home.svg');

        $this->assertSame('private, max-age=31536000, immutable', $response->headers()->get('Cache-Control'));
    }

    public function testScriptChunksAreCachedImmutably(): void
    {
        $response = $this->asset('js', 'chunks/chunk-abc.js');

        $this->assertSame('private, max-age=31536000, immutable', $response->headers()->get('Cache-Control'));
    }

    public function testOnlyScriptChunksAreCachedImmutablyByPath(): void
    {
        $response = $this->asset('js', 'app.js');

        $this->assertFalse($response->headers()->has('Cache-Control') && str_contains($response->headers()->get('Cache-Control'), 'immutable'));
    }

    public function testChunksPathOfOtherTypesIsNotCachedImmutably(): void
    {
        FileSystem::createDirectory($this->assetsPath . '/css/chunks');
        FileSystem::write($this->assetsPath . '/css/chunks/part.css', 'css');

        $response = $this->asset('css', 'chunks/part.css');

        $this->assertFalse($response->headers()->has('Cache-Control') && str_contains($response->headers()->get('Cache-Control'), 'immutable'));
    }

    /**
     * @param array<string, string> $query
     */
    private function asset(string $type, string $file, array $query = []): Response
    {
        $controller = $this->makeController(
            AssetsController::class,
            [],
            ['panel' => ['paths' => ['assets' => $this->assetsPath]]],
            query: $query,
        );

        return $controller->asset(new RouteParams(['type' => $type, 'file' => $file]));
    }

    private function responseFile(FileResponse $fileResponse): string
    {
        return (string) (new ReflectionProperty(FileResponse::class, 'path'))->getValue($fileResponse);
    }
}
