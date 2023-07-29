<?php

namespace Formwork\Files;

use Formwork\Data\AbstractCollection;
use Formwork\Utils\Arr;

class FileCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = File::class;

    public function __construct(array $data = [])
    {
        if (!Arr::isAssociative($data)) {
            $data = Arr::mapKeys($data, fn ($key, File $file) => $file->name());
        }

        parent::__construct($data);
    }
}
