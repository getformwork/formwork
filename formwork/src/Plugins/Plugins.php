<?php

namespace Formwork\Plugins;

use Formwork\Config\Config;
use Formwork\Events\EventDispatcher;
use Formwork\Plugins\Events\PluginsInitializedEvent;
use Formwork\Plugins\Exceptions\PluginInitializationException;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use InvalidArgumentException;
use Throwable;
use UnexpectedValueException;

/**
 * @since 2.3.0
 */
class Plugins extends PluginCollection
{
    public function __construct(
        private Config $config,
        private PluginFactory $pluginFactory,
        private EventDispatcher $eventDispatcher
    ) {}

    /**
     * Load a plugin
     */
    public function load(string $name, string $path): void
    {
        $plugin = $this->pluginFactory->make($path);
        $this->data[$name] = $plugin;
    }

    /**
     * Load plugin files from a path
     */
    public function loadFromPath(string $path): void
    {
        foreach (FileSystem::listContents($path) as $item) {
            $name = Str::toCamelCase($item);
            $this->load($name, FileSystem::joinPaths($path, $item));
        }
    }

    /**
     * Initialize a plugin from id
     *
     * @throws InvalidArgumentException      If the plugin id is invalid
     * @throws PluginInitializationException If the plugin autoload or initialization fails
     */
    public function initialize(string $name): void
    {
        if (($plugin = $this->get($name)) === null) {
            throw new InvalidArgumentException(sprintf('Invalid plugin "%s"', $name));
        }

        try {
            if ($autoload = $plugin->autoload()) {
                // Requiring vendor/autoload.php always prepends the autoloader to the stack,
                // so we need to unregister and re-register without prepending
                $autoload->unregister();
                $autoload->register(prepend: false);
            }
        } catch (Throwable $e) {
            throw new PluginInitializationException(sprintf('Failed autoload for plugin "%s"', $name), $e->getCode(), previous: $e);
        }

        foreach ($plugin->getEventListeners() as $eventName => $eventListener) {
            $this->eventDispatcher->on($eventName, $plugin->{$eventListener}(...));
        }

        try {
            $plugin->initialize();
        } catch (Throwable $e) {
            throw new PluginInitializationException(sprintf('Failed initialization for plugin "%s"', $name), $e->getCode(), previous: $e);
        }
    }

    /**
     * Initialize all enabled plugins
     */
    public function initializeEnabled(): void
    {
        foreach ($this->keys() as $name) {
            if (!is_string($name)) {
                throw new UnexpectedValueException('Unexpected non-string plugin name');
            }

            if (!$this->config->getBool("plugins.{$name}.enabled", false)) {
                continue;
            }

            $this->initialize($name);
        }

        $this->eventDispatcher->dispatch(new PluginsInitializedEvent($this));
    }
}
