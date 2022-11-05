<?php

namespace Formwork\Metadata;

use Formwork\Data\AssociativeCollection;

class Metadata extends AssociativeCollection
{
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
    public function set(string $name, string $content): void
    {
        $this->data[$name] = new Metadatum($name, $content);
    }

    /**
     * Set multiple metadata
     */
    public function setMultiple(array $data): void
    {
        foreach ($data as $name => $content) {
            $this->set($name, $content);
        }
    }
}
