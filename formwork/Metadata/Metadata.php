<?php

namespace Formwork\Metadata;

use Formwork\Data\AssociativeCollection;

class Metadata extends AssociativeCollection
{
    /**
     * Create a new Metadata instance
     */
    public function __construct(array $items)
    {
        parent::__construct();
        $this->setMultiple($items);
    }

    /**
     * Set a metadatum
     */
    public function set(string $name, string $content)
    {
        $this->items[$name] = new Metadatum($name, $content);
    }

    /**
     * Set multiple metadata
     */
    public function setMultiple(array $items)
    {
        foreach ($items as $name => $content) {
            $this->set($name, $content);
        }
    }
}
