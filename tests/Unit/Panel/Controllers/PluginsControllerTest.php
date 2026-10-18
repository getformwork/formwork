<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Panel\Controllers\PluginsController;
use Formwork\Parsers\Yaml;
use Formwork\Plugins\Plugin;
use Formwork\Services\Container;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\Fixtures\BuildsPanelControllers;
use Formwork\Tests\Unit\Panel\Fixtures\ProvidesFormSaveCases;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

#[CoversClass(PluginsController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class PluginsControllerTest extends TestCase
{
    use BuildsPanelControllers;
    use ProvidesFormSaveCases;

    /**
     * @param array<string, mixed> $existingOptions
     * @param array<string, mixed> $formData
     * @param array<string, mixed> $expected
     */
    #[DataProvider('formSaveProvider')]
    public function testUpdatingPluginOptionsSavesOnlyOverridesAndReplacesArrays(array $existingOptions, array $formData, array $expected): void
    {
        $pluginDefaults = self::formSaveDefaults();

        $app = App::instance();
        $pluginId = 'panel-options-data-provider';
        $pluginName = 'panelOptionsDataProvider';
        $pluginPath = TESTS_TMP_PATH . '/plugins/' . $pluginId;
        $outputPath = $app->rootPath() . "/site/config/plugins/{$pluginId}.yaml";
        $originalOutput = FileSystem::isFile($outputPath, assertExists: false)
            ? FileSystem::read($outputPath)
            : null;

        FileSystem::createDirectory($pluginPath, recursive: true);
        Yaml::encodeToFile(['config' => $pluginDefaults], $pluginPath . '/plugin.yaml');

        try {
            $app->schemes();
            $plugin = new Plugin($pluginPath, $app->getService(Container::class), $app);
            $config = new Config(['plugins' => [$pluginName => $existingOptions]], resolved: true);
            $controller = $this->makeController(
                PluginsController::class,
                ['panel.plugins' => true],
                services: [Config::class => $config],
            );
            $reflectionMethod = new ReflectionMethod($controller, 'updatePluginsOptions');
            $reflectionMethod->invoke($controller, $plugin, $formData);

            $this->assertSame($expected, Yaml::parseFile($outputPath));
        } finally {
            if ($originalOutput === null) {
                if (is_file($outputPath)) {
                    FileSystem::delete($outputPath);
                }
            } else {
                FileSystem::write($outputPath, $originalOutput);
            }

            FileSystem::deleteDirectory($pluginPath, recursive: true);
            $this->closePanelSession();
        }
    }
}
