<?php

namespace Formwork\Tests\Unit\Plugins\Fixtures;

use Formwork\Cms\App;
use Formwork\Plugins\Plugin;
use Formwork\Services\Container;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;

/**
 * Generates plugin directories in the temporary path
 *
 * Plugin classes are declared in the global scope of the process, so every test must use its own plugin ids
 *
 * @mixin \Formwork\Tests\TestCase
 */
trait BuildsPlugins
{
    protected string $pluginsPath;

    protected function setUpPlugins(): void
    {
        $this->setUpTempDirectory();
        $this->pluginsPath = TESTS_TMP_PATH . '/plugins-sandbox';
        FileSystem::createDirectory($this->pluginsPath, recursive: true);
    }

    protected function tearDownPlugins(): void
    {
        $this->tearDownTempDirectory();
    }

    /**
     * Create a plugin directory with its class file and return the path
     *
     * @param array<string, string> $files Additional files, relative to the plugin directory
     */
    protected function makePluginDirectory(string $id, string $classBody = '', array $files = []): string
    {
        $path = $this->pluginsPath . '/' . $id;
        if (!FileSystem::isDirectory($path, assertExists: false)) {
            FileSystem::createDirectory($path, recursive: true);
        }

        $className = ucfirst(Str::toCamelCase($id)) . 'Plugin';

        FileSystem::write($path . '/' . $id . '.php', "<?php\n\nnamespace Formwork\\Plugins;\n\nclass {$className} extends Plugin\n{\n{$classBody}\n}\n");

        foreach ($files as $name => $content) {
            if (!FileSystem::isDirectory(dirname($path . '/' . $name), assertExists: false)) {
                FileSystem::createDirectory(dirname($path . '/' . $name), recursive: true);
            }
            FileSystem::write($path . '/' . $name, $content);
        }

        return $path;
    }

    /**
     * Create a plugin directory and instantiate its class
     *
     * @param array<string, string> $files
     */
    protected function makePlugin(string $id, string $classBody = '', array $files = []): Plugin
    {
        $path = $this->makePluginDirectory($id, $classBody, $files);

        require_once $path . '/' . $id . '.php';

        /** @var class-string<Plugin> $className */
        $className = 'Formwork\Plugins\\' . ucfirst(Str::toCamelCase($id)) . 'Plugin';

        $container = new Container();
        $container->define(Container::class, $container);

        return new $className($path, $container, App::instance());
    }
}
