<?php

namespace Formwork\Files;

use Formwork\Data\AbstractCollection;
use Formwork\Utils\FileSystem;

class Files extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = File::class;

    /**
     * Filter files by a given type
     */
    public function filterByType(string $type): self
    {
        $files = clone $this;
        $files->data = array_filter($files->data, static fn (File $item): bool => $item->type() === $type);
        return $files;
    }

    /**
     * Create a collection getting files from a given path
     *
     * @param array|null $filenames Array of file names to include (all files by default)
     */
    public static function fromPath(string $path, ?array $filenames = null): self
    {
        $filenames ??= FileSystem::listFiles($path);

        $files = [];

        foreach ($filenames as $filename) {
            $files[$filename] = new File($path . $filename);
        }

        return new static($files);
    }
}
