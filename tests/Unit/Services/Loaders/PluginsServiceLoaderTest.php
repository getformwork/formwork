<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Events\EventDispatcher;
use Formwork\Plugins\Plugins;
use Formwork\Services\Container;
use Formwork\Services\Loaders\PluginsServiceLoader;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Plugins\Fixtures\BuildsPlugins;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PluginsServiceLoader::class)]
final class PluginsServiceLoaderTest extends TestCase
{
    use BuildsPlugins;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPlugins();
    }

    protected function tearDown(): void
    {
        $this->tearDownPlugins();
        parent::tearDown();
    }

    public function testPluginsOfThePluginsDirectoryAreLoadedByCamelCasedName(): void
    {
        $this->makePluginDirectory('loader-alpha');
        $this->makePluginDirectory('loader-beta');

        $plugins = $this->resolve();

        $this->assertEqualsCanonicalizing(['loaderAlpha', 'loaderBeta'], $plugins->keys());
    }

    public function testNothingIsLoadedWhenPluginsAreDisabled(): void
    {
        $this->makePluginDirectory('loader-disabled');

        $this->assertCount(0, $this->resolve(enabled: false));
    }

    public function testMissingPluginsDirectoryIsTolerated(): void
    {
        $this->assertCount(0, $this->resolve(path: TESTS_TMP_PATH . '/does-not-exist'));
    }

    public function testDirectoriesWithoutAPluginClassAreIgnored(): void
    {
        FileSystem::createDirectory($this->pluginsPath . '/loader-empty');
        FileSystem::write($this->pluginsPath . '/loader-empty/readme.md', 'not a plugin');
        $this->makePluginDirectory('loader-valid');

        $this->assertSame(['loaderValid'], $this->resolve()->keys());
    }

    public function testPluginsWithAMismatchingClassAreIgnored(): void
    {
        FileSystem::createDirectory($this->pluginsPath . '/loader-wrong');
        FileSystem::write($this->pluginsPath . '/loader-wrong/loader-wrong.php', "<?php\nnamespace Formwork\\Plugins;\nclass LoaderOtherPlugin extends Plugin {}\n");

        $this->assertCount(0, $this->resolve());
    }

    public function testPluginsWithInvalidIdsDoNotBringTheSiteDown(): void
    {
        FileSystem::createDirectory($this->pluginsPath . '/Loader_Invalid');
        FileSystem::write($this->pluginsPath . '/Loader_Invalid/Loader_Invalid.php', "<?php\nnamespace Formwork\\Plugins;\nclass LoaderInvalidPlugin extends Plugin {}\n");
        $this->makePluginDirectory('loader-fine');

        $plugins = $this->resolve();

        $this->assertSame(['loaderFine'], $plugins->keys());
    }

    public function testFilesInThePluginsDirectoryAreIgnored(): void
    {
        FileSystem::write($this->pluginsPath . '/README.md', '# Plugins');
        $this->makePluginDirectory('loader-readme');

        $this->assertSame(['loaderReadme'], $this->resolve()->keys());
    }

    public function testLoadedPluginsAreNotInitialized(): void
    {
        $this->makePluginDirectory('loader-lazy');

        $this->assertFalse($this->resolve()->get('loaderLazy')->isInitialized());
    }

    private function resolve(bool $enabled = true, ?string $path = null): Plugins
    {
        $config = new Config(['system' => ['plugins' => ['enabled' => $enabled, 'path' => $path ?? $this->pluginsPath]]], resolved: true);

        $container = new Container();
        $container->define(Container::class, $container);
        $container->define(App::class, App::instance());
        $container->define(Config::class, $config);
        $container->define(EventDispatcher::class, new EventDispatcher());
        $container->define(Plugins::class)->loader(PluginsServiceLoader::class);

        return $container->get(Plugins::class);
    }
}
