<?php

namespace Formwork\Admin\Utils;

class Log extends Registry
{
    /**
     * Limit of registry entries
     *
     * @var int
     */
    protected $limit;

    /**
     * Create a new Log instance
     *
     * @param string $filename
     * @param int    $limit
     */
    public function __construct(string $filename, int $limit = 128)
    {
        parent::__construct($filename);
        $this->limit = $limit;
    }

    /**
     * Log a message at current time with microseconds
     *
     * @param string $message
     *
     * @return string Logging timestamp
     */
    public function log(string $message)
    {
        $timestamp = (string) microtime(true);
        $this->set($timestamp, $message);
        return $timestamp;
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        if (count($this->storage) > $this->limit) {
            $this->storage = array_slice($this->storage, -$this->limit, null, true);
        }
        parent::save();
    }
}
