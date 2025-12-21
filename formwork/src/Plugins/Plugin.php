<?php

namespace Formwork\Plugins;

use Composer\Autoload\ClassLoader;
use Formwork\Cms\App;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Parsers\Yaml;
use Formwork\Plugins\Controllers\AssetsController;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use Formwork\View\ViewFactory;
use InvalidArgumentException;
use ReflectionMethod;

class Plugin implements Arrayable
{
    /**
     * Plugin id
     */
    protected string $id;

    /**
     * Whether the plugin has been initialized
     */
    protected bool $initialized = false;

    /**
     * Plugin manifest
     */
    protected PluginManifest $manifest;

    final public function __construct(
        protected string $path,
        protected App $app,
        protected ViewFactory $viewFactory
    ) {
        $this->id = basename($this->path);

        if (!preg_match('/^[a-z0-9-]+$/', $this->id)) {
            throw new InvalidArgumentException(sprintf('Invalid plugin id "%s". Plugin ids can only contain lowercase letters, numbers and hyphens.', $this->id));
        }
    }

    /**
     * Get the plugin path
     */
    final public function path(): string
    {
        return $this->path;
    }

    /**
     * Get the plugin ID
     */
    final public function id(): string
    {
        return $this->id;
    }

    /**
     * Get the plugin name
     */
    final public function name(): string
    {
        return Str::toCamelCase($this->id());
    }

    /**
     * Get the plugin namespace
     */
    final public function namespace(): string
    {
        return "plugin:{$this->id()}";
    }

    /**
     * Get the plugin manifest
     */
    final public function manifest(): PluginManifest
    {
        if (!isset($this->manifest)) {
            $this->loadManifest();
        }
        return $this->manifest;
    }

    /**
     * Method called when the plugin is initialized
     */
    final public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->loadConfig();
        $this->loadSchemes();
        $this->loadTranslations();
        $this->loadViews();
        $this->loadAssets();

        $this->initialized = true;
    }

    /**
     * Return whether the plugin is enabled
     */
    final public function isEnabled(): bool
    {
        return $this->app->config()->get("plugins.{$this->name()}.enabled", false);
    }

    /**
     * Return whether the plugin has been initialized
     */
    final public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Get the plugin autoloader
     */
    public function autoload(): ?ClassLoader
    {
        return null;
    }

    /**
     * Get the event listeners defined in the plugin and their corresponding methods
     *
     * @return array<string, string>
     */
    public function getEventListeners(): array
    {
        $handlers = [];

        foreach (get_class_methods($this) as $method) {
            $reflection = new ReflectionMethod($this, $method);
            if ($reflection->isPublic() && Str::startsWith($method, 'on')) {
                $eventName = lcfirst(Str::after($method, 'on'));
                $handlers[$eventName] = $method;
            }
        }

        return $handlers;
    }

    /**
     * Get the plugin data as an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'          => $this->id(),
            'name'        => $this->name(),
            'path'        => $this->path(),
            'manifest'    => $this->manifest()->toArray(),
            'enabled'     => $this->isEnabled(),
            'initialized' => $this->isInitialized(),
        ];
    }

    /**
     * Load plugin manifest
     */
    protected function loadManifest(): void
    {
        $manifestPath = FileSystem::joinPaths($this->path, 'plugin.yaml');

        $data = FileSystem::isFile($manifestPath, assertExists: false)
            ? Yaml::parseFile($manifestPath)
            : [];

        $this->manifest = new PluginManifest($data);
    }

    /**
     * Load plugin configuration into application config
     */
    protected function loadConfig(): void
    {
        foreach ($this->manifest()->config() as $key => $value) {
            if (!$this->app->config()->has("plugins.{$this->id()}.{$key}")) {
                $this->app->config()->set("plugins.{$this->id()}.{$key}", $value);
            }
        }
    }

    /**
     * Load plugin schemes
     */
    protected function loadSchemes(): void
    {
        $schemesPath = FileSystem::joinPaths($this->path(), 'schemes');
        if (FileSystem::isDirectory($schemesPath, assertExists: false)) {
            $this->app->schemes()->loadFromPath($schemesPath);
        }
    }

    /**
     * Load plugin translations
     */
    protected function loadTranslations(): void
    {
        $translationsPath = FileSystem::joinPaths($this->path(), 'translations');
        if (FileSystem::isDirectory($translationsPath, assertExists: false)) {
            $this->app->translations()->loadFromPath($translationsPath);
        }
    }

    /**
     * Load plugin views
     */
    protected function loadViews(): void
    {
        $viewsPath = FileSystem::joinPaths($this->path(), 'views');
        if (FileSystem::isDirectory($viewsPath, assertExists: false)) {
            $this->viewFactory->setResolutionPaths([$this->namespace() => $viewsPath]);
        }
    }

    /**
     * Load plugin assets
     */
    protected function loadAssets(): void
    {
        $assetsPath = FileSystem::joinPaths($this->path(), 'assets');
        if (FileSystem::isDirectory($assetsPath, assertExists: false)) {
            $this->app->assets()->setResolutionPaths([
                $this->namespace() => [
                    'path' => $assetsPath,
                    'uri'  => $this->app->site()->uri("/plugins/{$this->id()}/assets", includeLanguage: false),
                ],
            ]);

            $this->app->router()->addRoute("{$this->namespace()}.assets", "/plugins/{$this->id()}/assets/{type:alpha}/{file:all}/")
                ->methods('GET')
                ->action(AssetsController::class . '@asset')
                ->actionParameters(['plugin' => $this]);
        }
    }
}
