<?php

namespace Formwork\Metadata;

use Formwork\Data\Collection;

class Metadata extends Collection
{
    /**
     * Create a new Metadata instance
     */
    public function __construct($items)
    {
        $this->setMultiple($items);
    }

    /**
     * Return whether a metadatum in the collection
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->items[$name]);
    }

    /**
     * Return a metadatum by name
     *
     * @param string $name
     *
     * @return Metadatum
     */
    public function get($name)
    {
        if ($this->has($name)) {
            return $this->items[$name];
        }
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
