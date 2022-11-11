<?php

namespace Formwork\Fields;

use Formwork\Data\AbstractCollection;
use Formwork\Data\DataGetter;

class Fields extends AbstractCollection
{
    protected ?string $dataType = Field::class;

    /**
     * Create a new Fields instance
     *
     * @param array $fields Array of Field objects
     */
    public function __construct(array $fields)
    {
        parent::__construct();
        foreach ($fields as $key => $value) {
            if ($value === null) {
                continue;
            }
            if ($value instanceof Field) {
                if (is_int($key)) {
                    $key = $value->name();
                }
                $this->data[$key] = $value;
            } else {
                $this->data[$key] = new Field($key, $value);
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
        foreach ($this->data as $key => $value) {
            if ($key === $field) {
                return $this->data[$key];
            }
            if ($value->has('fields')) {
                $found = $value->get('fields')->find($field);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        return null;
    }

    /**
     * Convert fields to array
     *
     * @param bool $flatten Whether to recursively convert Fields instances
     */
    public function toArray(bool $flatten = false): array
    {
        if (!$flatten) {
            return $this->data;
        }
        $result = [];
        foreach ($this->data as $key => $value) {
            if ($value->has('fields')) {
                $result = array_merge($result, $value->get('fields')->toArray(true));
            } else {
                $result[$key] = $value;
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
        return $this->data;
    }
}
