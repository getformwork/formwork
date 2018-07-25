<?php

namespace Formwork\Files;

use Formwork\Data\Collection;
use Formwork\Utils\FileSystem;

class Files extends Collection
{
    protected $path;

    public function __construct($files = array(), $path)
    {
        $this->path = FileSystem::normalize($path);
        foreach ($files as $file) {
            $this->items[$file] = new File($this->path . $file);
        }
    }

    public function has($file)
    {
        return isset($this->items[$file]);
    }

    public function path()
    {
        return $this->path;
    }

    public function filterByType($type)
    {
        $files = clone $this;
        $files->items = array_filter($files->items, function ($item) use ($type) {
            return $item->type() === $type;
        });
        return $files;
    }
}
