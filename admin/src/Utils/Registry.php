<?php

namespace Formwork\Admin\Utils;

class Registry {

    protected $storage = array();

    protected $filename;

    public function __construct($filename) {
        $this->filename = $filename;
        if (file_exists($this->filename)) {
            $this->storage = (array) json_decode(file_get_contents($filename), true);
        }
    }

    public function __destruct() {
        $this->save();
    }

    public function has($key) {
        return isset($this->storage[$key]);
    }

    public function get($key) {
        if ($this->has($key)) return $this->storage[$key];
    }

    public function set() {
        list($key, $value) = func_get_args();
        $this->storage[$key] = $value;
    }

    public function remove($key) {
        if ($this->has($key)) unset($this->storage[$key]);
    }

    public function save() {
        file_put_contents($this->filename, json_encode($this->storage));
    }

    public function toArray() {
        return $this->storage;
    }

}
