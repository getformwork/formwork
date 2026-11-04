<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Data\AbstractCollection;
use Formwork\Http\JsonResponse;
use Formwork\Http\RedirectResponse;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Controllers\PluginsController;
use Formwork\Parsers\Yaml;
use Formwork\Plugins\Plugin;
use Formwork\Router\RouteParams;
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
use ReflectionProperty;

#[CoversClass(PluginsController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class PluginsControllerTest extends TestCase
{
    use BuildsPanelControllers;
    use ProvidesFormSaveCases;

    /**
     * @var list<string>
     */
    private array $loadedPlugins = [];

    /**
     * @var array<string, Plugin>|null
     */
    private ?array $originalPlugins = null;

    protected function tearDown(): void
    {
        if ($this->originalPlugins !== null) {
            (new ReflectionProperty(AbstractCollection::class, 'data'))->setValue(App::instance()->plugins(), $this->originalPlugins);
            $this->originalPlugins = null;
        }

        foreach ($this->loadedPlugins as $id) {
            if (FileSystem::exists($this->configFile($id))) {
                FileSystem::delete($this->configFile($id));
            }
        }

        $this->loadedPlugins = [];

        $this->closePanelSession();
        parent::tearDown();
    }

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

    public function testIndexRequiresThePermission(): void
    {
        $response = $this->controller(['panel.plugins' => false])->index();

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testIndexListsThePluginsSortedByTitle(): void
    {
        $this->loadPlugins();

        $view = $this->renderedView($this->controller()->index());

        $this->assertSame('@panel.plugins.index', $view['view']);
        $this->assertSame(['Alpha plugin', 'Beta plugin', 'Gamma plugin'], array_values(array_map(fn(array $plugin) => $plugin['manifest']['title'], $view['vars']['plugins'])));
    }

    public function testPluginRequiresThePermission(): void
    {
        $this->loadPlugins();

        $response = $this->controller(['panel.plugins' => false])->plugin(new RouteParams(['id' => 'alpha']), App::instance()->plugins());

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testUnknownPluginsAreNotFound(): void
    {
        $response = $this->controller()->plugin(new RouteParams(['id' => 'missing']), App::instance()->plugins());

        $this->assertSame(ResponseStatus::NotFound, $response->status());
    }

    public function testPluginFormIsDisplayedWithItsNeighbours(): void
    {
        $this->loadPlugins();

        $response = $this->controller(server: ['REQUEST_METHOD' => 'GET'])->plugin(new RouteParams(['id' => 'beta']), App::instance()->plugins());
        $view = $this->renderedView($response);

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertSame('@panel.plugins.plugin', $view['view']);
        $this->assertSame('Beta plugin', $view['vars']['title']);
        $this->assertSame('alpha', $view['vars']['previousPlugin']['id']);
        $this->assertSame('gamma', $view['vars']['nextPlugin']['id']);
        $this->assertArrayNotHasKey('enabled', $view['vars']['fields']);
    }

    public function testTheFirstPluginHasNoPreviousPlugin(): void
    {
        $this->loadPlugins();

        $view = $this->renderedView($this->controller(server: ['REQUEST_METHOD' => 'GET'])->plugin(new RouteParams(['id' => 'alpha']), App::instance()->plugins()));

        $this->assertNull($view['vars']['previousPlugin']);
        $this->assertSame('beta', $view['vars']['nextPlugin']['id']);
    }

    public function testTheLastPluginHasNoNextPlugin(): void
    {
        $this->loadPlugins();

        $view = $this->renderedView($this->controller(server: ['REQUEST_METHOD' => 'GET'])->plugin(new RouteParams(['id' => 'gamma']), App::instance()->plugins()));

        $this->assertSame('beta', $view['vars']['previousPlugin']['id']);
        $this->assertNull($view['vars']['nextPlugin']);
    }

    public function testInvalidPluginOptionsAreNotSaved(): void
    {
        $this->loadPlugins();

        $response = $this->controller(input: ['message' => ''], server: ['REQUEST_METHOD' => 'POST'])->plugin(new RouteParams(['id' => 'alpha']), App::instance()->plugins());

        $this->assertSame(ResponseStatus::UnprocessableEntity, $response->status());
        $this->assertFileDoesNotExist($this->configFile('alpha'));
    }

    public function testValidPluginOptionsAreSaved(): void
    {
        $this->loadPlugins();

        $response = $this->controller(input: ['message' => 'Hello'], server: ['REQUEST_METHOD' => 'POST'])->plugin(new RouteParams(['id' => 'alpha']), App::instance()->plugins());

        $this->assertInstanceOf(RedirectResponse::class, $response, $response->content());
        $this->assertSame('Hello', Yaml::parseFile($this->configFile('alpha'))['message']);
    }

    public function testEnablingRequiresThePermission(): void
    {
        $this->loadPlugins();

        $response = $this->controller(['panel.plugins' => false])->enable(new RouteParams(['id' => 'beta']), App::instance()->plugins());

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
        $this->assertFileDoesNotExist($this->configFile('beta'));
    }

    public function testUnknownPluginsCannotBeEnabled(): void
    {
        $response = $this->controller()->enable(new RouteParams(['id' => 'missing']), App::instance()->plugins());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::NotFound, $response->status());
    }

    public function testPluginsWithInvalidOptionsCannotBeEnabled(): void
    {
        $this->loadPlugins();

        $response = $this->controller()->enable(new RouteParams(['id' => 'alpha']), App::instance()->plugins());

        $this->assertSame(ResponseStatus::UnprocessableEntity, $response->status());
        $this->assertFileDoesNotExist($this->configFile('alpha'));
    }

    public function testPluginsCanBeEnabled(): void
    {
        $this->loadPlugins();

        $response = $this->controller()->enable(new RouteParams(['id' => 'beta']), App::instance()->plugins());

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertTrue(Yaml::parseFile($this->configFile('beta'))['enabled']);
    }

    public function testDisablingRequiresThePermission(): void
    {
        $this->loadPlugins();

        $response = $this->controller(['panel.plugins' => false])->disable(new RouteParams(['id' => 'beta']), App::instance()->plugins());

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testUnknownPluginsCannotBeDisabled(): void
    {
        $response = $this->controller()->disable(new RouteParams(['id' => 'missing']), App::instance()->plugins());

        $this->assertSame(ResponseStatus::NotFound, $response->status());
    }

    public function testPluginsCanBeDisabled(): void
    {
        $this->loadPlugins();

        $response = $this->controller()->disable(new RouteParams(['id' => 'beta']), App::instance()->plugins());

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertFalse(Yaml::parseFile($this->configFile('beta'))['enabled']);
    }

    /**
     * Load some plugins in the plugins of the application, they are removed after the test
     */
    private function loadPlugins(): void
    {
        $plugins = App::instance()->plugins();
        $data = new ReflectionProperty(AbstractCollection::class, 'data');
        $this->originalPlugins = $data->getValue($plugins);
        $loaded = $this->originalPlugins;

        foreach (['alpha' => 'Alpha plugin', 'beta' => 'Beta plugin', 'gamma' => 'Gamma plugin'] as $id => $title) {
            $path = TESTS_TMP_PATH . "/plugins/{$id}";

            FileSystem::createDirectory($path, recursive: true);
            Yaml::encodeToFile(['title' => $title, 'version' => '1.0.0'], $path . '/plugin.yaml');

            if ($id === 'alpha') {
                FileSystem::createDirectory($path . '/schemes/plugins', recursive: true);
                Yaml::encodeToFile(['title' => $title, 'extend' => 'plugins.plugin', 'fields' => ['message' => ['type' => 'text', 'label' => 'Message', 'required' => true]]], $path . '/schemes/plugins/alpha.yaml');
            }

            $loaded[$id] = new Plugin($path, App::instance()->getService(Container::class), App::instance());
            $this->loadedPlugins[] = $id;
        }

        // The collection is immutable, so the plugins are replaced directly
        $data->setValue($plugins, $loaded);
    }

    private function configFile(string $id): string
    {
        return App::instance()->rootPath() . "/site/config/plugins/{$id}.yaml";
    }

    /**
     * @param array<string, bool>   $permissions
     * @param array<string, mixed>  $input
     * @param array<string, string> $server
     */
    private function controller(array $permissions = ['panel.plugins' => true], array $input = [], array $server = []): PluginsController
    {
        return $this->makeController(PluginsController::class, $permissions, input: $input, server: $server);
    }
}
