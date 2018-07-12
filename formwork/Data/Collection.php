<?php

namespace Formwork\Data;

use Iterator;

class Collection implements Iterator
{
    protected $items = array();

    public function __construct($items)
    {
        if (is_array($items)) {
            $this->items = $items;
        }
    }

    public function rewind()
    {
        reset($this->items);
    }

    public function current()
    {
        return current($this->items);
    }

    public function key()
    {
        return key($this->items);
    }

    public function next()
    {
        return next($this->items);
    }

    public function valid()
    {
        return $this->current() !== false;
    }

    public function count()
    {
        return count($this->items);
    }

    public function toArray()
    {
        return $this->items;
    }

    public function first()
    {
        return $this->items[0];
    }

    public function last()
    {
        return $this->items[$this->count() - 1];
    }

    public function empty()
    {
        return empty($this->items);
    }
}
