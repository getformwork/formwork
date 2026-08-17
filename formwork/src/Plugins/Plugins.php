<?php

namespace Formwork\Plugins;

use Formwork\Config\Config;
use Formwork\Events\EventDispatcher;
use Formwork\Plugins\Events\PluginsInitializedEvent;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use InvalidArgumentException;
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
     * @throws InvalidArgumentException If the plugin id is invalid
     */
    public function initialize(string $name): void
    {
        if (($plugin = $this->get($name)) === null) {
            throw new InvalidArgumentException(sprintf('Invalid plugin "%s"', $name));
        }

        $plugin->autoload()?->register();

        foreach ($plugin->getEventListeners() as $eventName => $eventListener) {
            $this->eventDispatcher->on($eventName, $plugin->{$eventListener}(...));
        }

        $plugin->initialize();
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

            if (!$this->config->get("plugins.{$name}.enabled")) {
                continue;
            }

            $this->initialize($name);
        }

        $this->eventDispatcher->dispatch(new PluginsInitializedEvent($this));
    }
}
