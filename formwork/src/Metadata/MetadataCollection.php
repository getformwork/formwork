<?php

namespace Formwork\Metadata;

use Formwork\Data\AbstractCollection;

class MetadataCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Metadata::class;

    protected bool $mutable = true;

    /**
     * Create a new Metadata instance
     */
    public function __construct(array $data)
    {
        parent::__construct();
        $this->setMultiple($data);
    }

    /**
     * Set a metadata
     */
    public function set(string $key, $value): void
    {
        $this->data[$key] = new Metadata($key, $value);
    }
}
