<?php

namespace Formwork\Tests\Unit\Controllers;

use Formwork\Controllers\AssetsController;
use Formwork\Http\FileResponse;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Router\RouteParams;
use Formwork\Router\Router;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Controllers\Fixtures\BuildsControllers;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

#[CoversClass(AssetsController::class)]
final class AssetsControllerTest extends TestCase
{
    use BuildsControllers;

    private string $processPath;

    private string $templatesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();

        $this->processPath = $this->createDirectory(TESTS_TMP_PATH . '/images');
        $this->templatesPath = $this->createDirectory(TESTS_TMP_PATH . '/templates');

        $this->createDirectory($this->processPath . '/abc123');
        FileSystem::write($this->processPath . '/abc123/photo.jpg', 'image bytes');
        FileSystem::write(TESTS_TMP_PATH . '/secret.txt', 'outside of the images directory');

        $this->createDirectory($this->templatesPath . '/assets/css');
        FileSystem::write($this->templatesPath . '/assets/css/site.css', 'body {}');
        FileSystem::write($this->templatesPath . '/secret.php', '<?php // template source');
        FileSystem::write(TESTS_TMP_PATH . '/outside.txt', 'outside of the templates directory');
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    // asset()

    public function testProcessedImagesAreServedWithLongLivedCacheHeaders(): void
    {
        $response = $this->controller()->asset(new RouteParams(['type' => 'images', 'id' => 'abc123', 'name' => 'photo.jpg']));

        $this->assertInstanceOf(FileResponse::class, $response);
        $this->assertSame($this->processPath . '/abc123/photo.jpg', $this->fileOf($response));
        $this->assertSame('private, max-age=31536000, immutable', $response->headers()->get('Cache-Control'));
    }

    public function testMissingImagesFallBackToTheErrorPage(): void
    {
        $response = $this->controller()->asset(new RouteParams(['type' => 'images', 'id' => 'abc123', 'name' => 'missing.jpg']));

        $this->assertSame(ResponseStatus::NotFound, $response->status());
        $this->assertSame('error page', $response->content());
    }

    public function testDirectoriesAreNotServed(): void
    {
        $response = $this->controller()->asset(new RouteParams(['type' => 'images', 'id' => 'abc123', 'name' => '.']));

        $this->assertSame(ResponseStatus::NotFound, $response->status());
    }

    public function testRoutesWithoutTypeAreRedirectedWithADeprecation(): void
    {
        $router = $this->createStub(Router::class);
        $router->method('rewrite')->willReturnCallback(static fn(array $params): string => '/assets/' . $params['type'] . '/abc123/photo.jpg/');
        $controller = $this->controller($router);
        $response = null;

        $messages = $this->captureDeprecations(function () use ($controller, &$response): void {
            $response = $controller->asset(new RouteParams(['id' => 'abc123', 'name' => 'photo.jpg']));
        });

        $this->assertCount(1, $messages);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(ResponseStatus::MovedPermanently, $response->status());
        $this->assertStringEndsWith('/assets/images/abc123/photo.jpg', (string) $response->headers()->get('Location'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function imageTraversals(): iterable
    {
        yield 'parent as id' => ['..', 'secret.txt'];
        yield 'parent in the name' => ['abc123', '../../secret.txt'];
        yield 'nested parents in the name' => ['abc123', 'x/../../../secret.txt'];
        yield 'backslashes' => ['abc123', '..\..\secret.txt'];
        yield 'url encoded parents' => ['abc123', '%2e%2e/%2e%2e/secret.txt'];
        yield 'null byte' => ['abc123', "photo.jpg\0../../secret.txt"];
    }

    #[DataProvider('imageTraversals')]
    public function testFilesOutsideTheImagesDirectoryAreNeverServed(string $id, string $name): void
    {
        $response = $this->controller()->asset(new RouteParams(['type' => 'images', 'id' => $id, 'name' => $name]));

        $this->assertNotInstanceOf(FileResponse::class, $response, 'Served ' . ($response instanceof FileResponse ? $this->fileOf($response) : ''));
        $this->assertSame(ResponseStatus::NotFound, $response->status());
    }

    // template()

    public function testTemplateAssetsAreServed(): void
    {
        $response = $this->controller()->template(new RouteParams(['file' => 'css/site.css']));

        $this->assertInstanceOf(FileResponse::class, $response);
        $this->assertSame($this->templatesPath . '/assets/css/site.css', $this->fileOf($response));
        $this->assertNull($response->headers()->get('Cache-Control'), 'Without a version query string the asset must not be cached forever');
    }

    public function testVersionedTemplateAssetsAreCachedForever(): void
    {
        $response = $this->controller(query: ['v' => 'abc'])->template(new RouteParams(['file' => 'css/site.css']));

        $this->assertSame('private, max-age=31536000, immutable', $response->headers()->get('Cache-Control'));
    }

    public function testMissingTemplateAssetsFallBackToTheErrorPage(): void
    {
        $response = $this->controller()->template(new RouteParams(['file' => 'css/missing.css']));

        $this->assertSame(ResponseStatus::NotFound, $response->status());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function templateTraversals(): iterable
    {
        yield 'parent directory' => ['../secret.php'];
        yield 'two parents' => ['../../outside.txt'];
        yield 'nested parents' => ['css/../../secret.php'];
        yield 'many parents' => ['../../../../../../../../../../etc/hostname'];
        yield 'backslashes' => ['..\secret.php'];
        yield 'absolute path' => ['/etc/hostname'];
    }

    #[DataProvider('templateTraversals')]
    public function testTemplateSourcesAndOtherFilesAreNotServedAsAssets(string $file): void
    {
        $response = $this->controller()->template(new RouteParams(['file' => $file]));

        $this->assertNotInstanceOf(FileResponse::class, $response, 'Served ' . ($response instanceof FileResponse ? $this->fileOf($response) : ''));
    }

    /**
     * @param array<string, mixed> $query
     */
    private function controller(?Router $router = null, array $query = []): AssetsController
    {
        return $this->makeController(AssetsController::class, [
            'images'    => ['processPath' => $this->processPath],
            'templates' => ['path' => $this->templatesPath],
        ], $query, router: $router);
    }

    private function fileOf(Response $response): string
    {
        return (string) (new ReflectionProperty($response, 'path'))->getValue($response);
    }
}
