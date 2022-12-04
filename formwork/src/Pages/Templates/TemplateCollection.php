<?php

namespace Formwork\Pages\Templates;

use Formwork\Data\AbstractCollection;
use Formwork\Utils\FileSystem;

class TemplateCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Template::class;

    /**
     * Load template from file
     */
    public function load(string $path): void
    {
        if (FileSystem::isReadable($path) && FileSystem::extension($path) === 'php') {
            $name = FileSystem::name($path);
            $this->data[$name] = $path;
        }
    }

    /**
     * Load templates from path
     */
    public function loadFromPath(string $path): void
    {
        foreach (FileSystem::listFiles($path) as $file) {
            $this->load(FileSystem::joinPaths($path, $file));
        }
    }

    /**
     * Create an instance with templates from the specified path
     */
    public static function fromPath(string $path): self
    {
        $instance = new static();
        $instance->loadFromPath($path);
        return $instance;
    }
}
