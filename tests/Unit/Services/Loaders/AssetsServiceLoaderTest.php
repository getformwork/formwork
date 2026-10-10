<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Formwork\Assets\Assets;
use Formwork\Cms\UriGenerator;
use Formwork\Config\Config;
use Formwork\Services\Container;
use Formwork\Services\Loaders\AssetsServiceLoader;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AssetsServiceLoader::class)]
final class AssetsServiceLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        FileSystem::createDirectory(TESTS_TMP_PATH . '/templates/assets/css', recursive: true);
        FileSystem::write(TESTS_TMP_PATH . '/templates/assets/css/style.css', 'body {}');
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testTemplateAssetsAreResolvedFromTheTemplatesDirectory(): void
    {
        $assets = $this->container()->get(Assets::class);

        $asset = $assets->get('css/style.css');

        $this->assertSame(FileSystem::normalizePath(TESTS_TMP_PATH . '/templates/assets/css/style.css'), $asset->path());
        $this->assertSame('/base/site/templates/assets/css/style.css', $asset->uri());
    }

    public function testTemplateNamespaceCanBeUsedExplicitly(): void
    {
        $assets = $this->container()->get(Assets::class);

        $this->assertSame($assets->get('@template/css/style.css')->path(), $assets->get('css/style.css')->path());
    }

    public function testAssetsAreEmptyUntilRequested(): void
    {
        $assets = $this->container()->get(Assets::class);

        $this->assertCount(0, $assets->stylesheets());
    }

    private function container(): Container
    {
        $uriGenerator = $this->createStub(UriGenerator::class);
        $uriGenerator->method('path')->willReturnCallback(static fn(string $path): string => '/base' . $path);

        $container = new Container();
        $container->define(Container::class, $container);
        $container->define(Config::class, new Config(['system' => ['templates' => ['path' => TESTS_TMP_PATH . '/templates']]], resolved: true));
        $container->define(UriGenerator::class, $uriGenerator);
        $container->define(Assets::class)->loader(AssetsServiceLoader::class);

        return $container;
    }
}
