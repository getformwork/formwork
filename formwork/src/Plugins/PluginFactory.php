<?php

namespace Formwork\Plugins;

use Formwork\Services\Container;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
use RuntimeException;

/**
 * @since 2.3.0
 */
class PluginFactory
{
    public function __construct(private Container $container) {}

    /**
     * Create a new Plugin instance
     *
     * @throws RuntimeException if the plugin class file or class is not found
     */
    public function make(string $path): Plugin
    {
        $name = basename($path);

        $classFile = FileSystem::joinPaths($path, Path::resolve($name, '/', DIRECTORY_SEPARATOR) . '.php');

        if (!is_file($classFile)) {
            throw new RuntimeException(sprintf('Plugin class file "%s" not found', $classFile));
        }

        require_once $classFile;

        /**
         * @var class-string $className
         */
        $className = 'Formwork\Plugins\\' . ucfirst(Str::toCamelCase($name)) . 'Plugin';

        if (!class_exists($className) || !is_subclass_of($className, Plugin::class)) {
            throw new RuntimeException(sprintf('Plugin class "%s" not found in file "%s"', $className, $classFile));
        }

        return $this->container->build($className, ['path' => $path]);
    }
}
