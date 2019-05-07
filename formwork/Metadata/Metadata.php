<?php

namespace Formwork\Metadata;

use Formwork\Data\AssociativeCollection;

class Metadata extends AssociativeCollection
{
    /**
     * Create a new Metadata instance
     */
    public function __construct($items)
    {
        $this->setMultiple($items);
    }

    /**
     * Set a metadatum
     *
     * @param string $name
     * @param string $content
     */
    public function set($name, $content)
    {
        $this->items[$name] = new Metadatum($name, $content);
    }

    /**
     * Set multiple metadata
     *
     * @param array $items
     */
    public function setMultiple($items)
    {
        foreach ($items as $name => $content) {
            $this->set($name, $content);
        }
    }
}
