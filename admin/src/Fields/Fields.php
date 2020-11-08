<?php

namespace Formwork\Admin\Fields;

use Formwork\Data\AssociativeCollection;
use Formwork\Data\DataGetter;

class Fields extends AssociativeCollection
{
    /**
     * Create a new Fields instance
     *
     * @param array $fields Array of Field objects
     */
    public function __construct(array $fields)
    {
        parent::__construct();
        foreach ($fields as $key => $value) {
            if ($value instanceof Field) {
                if (is_int($key)) {
                    $key = $value->name();
                }
                $this->items[$key] = $value;
            } else {
                $this->items[$key] = new Field($key, $value);
            }
        }
    }

    /**
     * Recursively find a field by name
     *
     * @param string $field Field name
     */
    public function find(string $field): ?Field
    {
        foreach ($this->items as $name => $data) {
            if ($name === $field) {
                return $this->items[$name];
            }
            if ($data->has('fields')) {
                $found = $data->get('fields')->find($field);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        return null;
    }

    /**
     * Render all the visible fields
     *
     * @param bool $return Whether to return or render the fields
     */
    public function render(bool $return = false)
    {
        $output = '';
        foreach ($this->items as $field) {
            $output .= $field->render(true);
        }
        if ($return) {
            return $output;
        }
        echo $output;
    }

    /**
     * Convert fields to array
     *
     * @param bool $flatten Whether to recursively convert Fields instances
     */
    public function toArray(bool $flatten = false): array
    {
        if (!$flatten) {
            return $this->items;
        }
        $result = [];
        foreach ($this->items as $name => $data) {
            if ($data->has('fields')) {
                $result = array_merge($result, $data->get('fields')->toArray(true));
            } else {
                $result[$name] = $data;
            }
        }
        return $result;
    }

    /**
     * Validate fields against data
     */
    public function validate(DataGetter $data): self
    {
        Validator::validate($this, $data);
        return $this;
    }

    public function __debugInfo(): array
    {
        return $this->items;
    }
}
