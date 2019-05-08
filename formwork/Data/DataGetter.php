<?php

namespace Formwork\Data;

use Formwork\Utils\Arr;

class DataGetter
{
    /**
     * Array containing data
     *
     * @var array
     */
    protected $data = array();

    /**
     * Create a new instance
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get data by key returning a default value if key is not present
     *
     * @param string     $key
     * @param mixed|null $default
     */
    public function get($key, $default = null)
    {
        return Arr::get($this->data, $key, $default);
    }

    /**
     * Return whether a key or multiple keys are present
     *
     * @param array|string $key
     *
     * @return bool
     */
    public function has($key)
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                if (!$this->has($k)) {
                    return false;
                }
            }
            return true;
        }
        return Arr::has($this->data, $key);
    }

    /**
     * Return an array containing data
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    public function __debugInfo()
    {
        return $this->toArray();
    }
}
