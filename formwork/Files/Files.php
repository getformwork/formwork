<?php

namespace Formwork\Files;

use Formwork\Data\Collection;
use Formwork\Utils\FileSystem;

class Files extends Collection
{
    /**
     * Root path of the file collection
     *
     * @var string
     */
    protected $path;

    /**
     * Create a new instance of Files
     *
     * @param array $files
     */
    public function __construct($files = array(), $path)
    {
        $this->path = FileSystem::normalize($path);
        foreach ($files as $file) {
            $this->items[$file] = new File($this->path . $file);
        }
    }

    /**
     * Return whether a file is present in the collection
     *
     * @param string $file
     *
     * @return bool
     */
    public function has($file)
    {
        return isset($this->items[$file]);
    }

    /**
     * Get files path
     *
     * @return string
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * Filter files by a given type
     *
     * @param string $type
     *
     * @return self
     */
    public function filterByType($type)
    {
        $files = clone $this;
        $files->items = array_filter($files->items, static function ($item) use ($type) {
            return $item->type() === $type;
        });
        return $files;
    }
}
