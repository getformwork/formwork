<?php

namespace Formwork\Tests\Unit\Plugins;

use Formwork\Cms\App;
use Formwork\Plugins\Plugin;
use Formwork\Plugins\PluginFactory;
use Formwork\Services\Container;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Plugins\Fixtures\BuildsPlugins;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

#[CoversClass(PluginFactory::class)]
final class PluginFactoryTest extends TestCase
{
    use BuildsPlugins;

    private PluginFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPlugins();

        $container = new Container();
        $container->define(Container::class, $container);
        $container->define(App::class, App::instance());
        $this->factory = new PluginFactory($container);
    }

    protected function tearDown(): void
    {
        $this->tearDownPlugins();
        parent::tearDown();
    }

    public function testPluginIsBuiltFromItsDirectory(): void
    {
        $path = $this->makePluginDirectory('factory-demo');

        $plugin = $this->factory->make($path);

        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertSame('Formwork\Plugins\FactoryDemoPlugin', $plugin::class);
        $this->assertSame('factory-demo', $plugin->id());
        $this->assertSame($path, $plugin->path());
    }

    public function testMissingClassFileIsReported(): void
    {
        $path = $this->pluginsPath . '/factory-empty';
        FileSystem::createDirectory($path, recursive: true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Plugin class file');

        $this->factory->make($path);
    }

    public function testClassFileWithoutTheExpectedClassIsReported(): void
    {
        $path = $this->pluginsPath . '/factory-wrong';
        FileSystem::createDirectory($path, recursive: true);
        FileSystem::write($path . '/factory-wrong.php', "<?php\nnamespace Formwork\\Plugins;\nclass SomethingElsePlugin extends Plugin {}\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Plugin class "Formwork\Plugins\FactoryWrongPlugin" not found');

        $this->factory->make($path);
    }

    public function testClassesNotExtendingPluginAreRejected(): void
    {
        $path = $this->pluginsPath . '/factory-notplugin';
        FileSystem::createDirectory($path, recursive: true);
        FileSystem::write($path . '/factory-notplugin.php', "<?php\nnamespace Formwork\\Plugins;\nclass FactoryNotpluginPlugin {}\n");

        $this->expectException(RuntimeException::class);

        $this->factory->make($path);
    }

    public function testInvalidPluginIdsAreRejectedBeforeInstantiation(): void
    {
        $path = $this->pluginsPath . '/Factory_Invalid';
        FileSystem::createDirectory($path, recursive: true);
        FileSystem::write($path . '/Factory_Invalid.php', "<?php\nnamespace Formwork\\Plugins;\nclass FactoryInvalidPlugin extends Plugin {}\n");

        $this->expectException(\InvalidArgumentException::class);

        $this->factory->make($path);
    }
}
