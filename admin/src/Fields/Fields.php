<?php

namespace Formwork\Admin\Fields;

use Formwork\Data\Collection;
use Formwork\Data\DataGetter;

class Fields extends Collection
{
    public function __construct($fields)
    {
        foreach ($fields as $name => $data) {
            $this->items[$name] = new Field($name, $data);
        }
    }

    public function has($field)
    {
        return isset($this->items[$field]);
    }

    public function get($field)
    {
        if ($this->has($field)) {
            return $this->items[$field];
        }
    }

    public function find($field)
    {
        foreach ($this->items as $name => $data) {
            if ($name === $field) {
                return $this->items[$name];
            }
            if ($data->has('fields')) {
                $found = $data->get('fields')->find($field);
                if (!is_null($found)) {
                    return $found;
                }
            }
        }
        return null;
    }

    public function toArray($flatten = false)
    {
        if (!$flatten) {
            return $this->items;
        }
        $result = array();
        foreach ($this->items as $name => $data) {
            if ($data->has('fields')) {
                $result = array_merge($result, $data->get('fields')->toArray(true));
            } else {
                $result[$name] = $data;
            }
        }
        return $result;
    }

    public function validate(DataGetter $data)
    {
        Validator::validate($this, $data);
        return $this;
    }

    public function __debugInfo()
    {
        return $this->items;
    }
}
