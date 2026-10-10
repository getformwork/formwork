<?php

namespace Formwork\Tests\Unit\Plugins\Controllers;

use Formwork\Http\FileResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Plugins\Controllers\AssetsController;
use Formwork\Plugins\Plugin;
use Formwork\Router\RouteParams;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Controllers\Fixtures\BuildsControllers;
use Formwork\Tests\Unit\Plugins\Fixtures\BuildsPlugins;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

#[CoversClass(AssetsController::class)]
final class AssetsControllerTest extends TestCase
{
    use BuildsControllers;
    use BuildsPlugins;

    private Plugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPlugins();

        $this->plugin = $this->makePlugin('assetsctl', '', [
            'assets/css/style.css' => 'body {}',
            'assets/js/script.js'  => 'console.log(1);',
            'secret.yaml'          => 'password: secret',
            'assets/private.txt'   => 'file in the assets root',
        ]);
        FileSystem::write(TESTS_TMP_PATH . '/plugins-sandbox/secret.txt', 'secret of another plugin directory');
    }

    protected function tearDown(): void
    {
        $this->tearDownPlugins();
        parent::tearDown();
    }

    public function testAssetsOfThePluginAreServed(): void
    {
        $response = $this->controller()->asset(new RouteParams(['type' => 'css', 'file' => 'style.css']));

        $this->assertInstanceOf(FileResponse::class, $response);
        $this->assertSame($this->pluginsPath . '/assetsctl/assets/css/style.css', $this->fileOf($response));
        $this->assertNull($response->headers()->get('Cache-Control'));
    }

    public function testVersionedAssetsAreCachedForever(): void
    {
        $response = $this->controller(['v' => '1'])->asset(new RouteParams(['type' => 'js', 'file' => 'script.js']));

        $this->assertSame('private, max-age=31536000, immutable', $response->headers()->get('Cache-Control'));
    }

    public function testMissingAssetsFallBackToTheErrorPage(): void
    {
        $response = $this->controller()->asset(new RouteParams(['type' => 'css', 'file' => 'missing.css']));

        $this->assertSame(ResponseStatus::NotFound, $response->status());
        $this->assertSame('error page', $response->content());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function traversals(): iterable
    {
        yield 'parent in the file' => ['css', '../../secret.yaml'];
        yield 'many parents in the file' => ['css', '../../../../../../../../../../etc/hostname'];
        yield 'nested parents in the file' => ['css', 'a/../../../secret.yaml'];
        yield 'backslashes in the file' => ['css', '..\..\secret.yaml'];
        yield 'parent as type' => ['..', 'secret.yaml'];
        yield 'parent as type and file' => ['..', '../secret.txt'];
        yield 'nested parents in the type' => ['css/../..', 'secret.yaml'];
        yield 'null byte' => ['css', "style.css\0../../secret.yaml"];
    }

    #[DataProvider('traversals')]
    public function testFilesOutsideTheAssetsDirectoryAreNeverServed(string $type, string $file): void
    {
        $response = $this->controller()->asset(new RouteParams(['type' => $type, 'file' => $file]));

        $this->assertNotInstanceOf(FileResponse::class, $response, 'Served ' . ($response instanceof FileResponse ? $this->fileOf($response) : ''));
    }

    /**
     * @param array<string, mixed> $query
     */
    private function controller(array $query = []): AssetsController
    {
        return $this->makeController(
            AssetsController::class,
            ['plugins' => ['path' => $this->pluginsPath]],
            $query,
            services: [Plugin::class => $this->plugin],
        );
    }

    private function fileOf(Response $response): string
    {
        return (string) (new ReflectionProperty($response, 'path'))->getValue($response);
    }
}
