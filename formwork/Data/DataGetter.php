<?php

namespace Formwork\Data;

class DataGetter {

    protected $data = array();

    public function __construct($data) {
        $this->data = $data;
    }

    public function get($key, $default = null) {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    public function has($key) {
        return array_key_exists($key, $this->data);
    }

    public function toArray() {
        return $this->data;
    }

    public function __debugInfo() {
        return $this->toArray();
    }

}
