<?php

namespace Formwork\Admin\Utils;

class Log extends Registry
{
    protected $limit;

    public function __construct($filename, $limit = 128)
    {
        parent::__construct($filename);
        $this->limit = $limit;
    }

    public function log($message)
    {
        $timestamp = (string) microtime(true);
        $this->set($timestamp, $message);
        return $timestamp;
    }

    public function save()
    {
        if (count($this->storage) > $this->limit) {
            $this->storage = array_slice($this->storage, -$this->limit, null, true);
        }
        parent::save();
    }
}
