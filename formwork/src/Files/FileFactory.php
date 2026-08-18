<?php

namespace Formwork\Files;

use Closure;
use Formwork\Config\Config;
use Formwork\Parsers\Yaml;
use Formwork\Schemes\Schemes;
use Formwork\Services\Container;
use Formwork\Utils\FileSystem;
use RuntimeException;

final class FileFactory
{
    /**
     * @param array<string, array{class-string, string}|class-string> $associations
     */
    public function __construct(
        private Container $container,
        private Config $config,
        private Schemes $schemes,
        private array $associations = [],
    ) {}

    /**
     * Create a new File instance
     */
    public function make(string $path): File
    {
        $mimeType = FileSystem::mimeType($path);

        $class = $this->associations[$mimeType] ?? File::class;

        if (is_array($class)) {
            [$class, $method] = $class;
        }

        $class = $this->container->build($class, compact('path'));

        $instance = isset($method)
            ? $this->container->call(Closure::fromCallable($class->$method(...)), compact('path'))
            : $class;

        if (!$instance instanceof File) {
            throw new RuntimeException(sprintf('Invalid object of type %s, only instances of %s are allowed', get_debug_type($instance), File::class));
        }

        $instance->setScheme($this->schemes->get($instance::SCHEME_IDENTIFIER));

        $metadataFile = $path . $this->config->getString('system.files.metadataExtension');

        $metadata = FileSystem::exists($metadataFile) ? Yaml::parseFile($metadataFile) : [];

        $instance->setMultiple($metadata);
        $instance->fields()->setValues($metadata);

        $instance->setUriGenerator($this->container->get(FileUriGenerator::class));

        return $instance;
    }
}
