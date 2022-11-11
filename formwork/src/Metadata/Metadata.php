<?php

namespace Formwork\Metadata;

use Formwork\Data\AbstractCollection;

class Metadata extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Metadatum::class;

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
     * Set a metadatum
     */
    public function set(string $key, $value)
    {
        $this->data[$key] = new Metadatum($key, $value);
    }
}
