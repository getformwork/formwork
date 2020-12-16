<?php

namespace Formwork\Utils;

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
     */
    public function __construct(string $filename, int $limit = 128)
    {
        parent::__construct($filename);
        $this->limit = $limit;
    }

    /**
     * Log a message at current time with microseconds
     *
     * @return string Logging timestamp
     */
    public function log(string $message): string
    {
        $timestamp = sprintf('%F', microtime(true));
        $this->set($timestamp, $message);
        return $timestamp;
    }

    /**
     * @inheritdoc
     */
    public function save(): void
    {
        if (count($this->storage) > $this->limit) {
            $this->storage = array_slice($this->storage, -$this->limit, null, true);
        }
        parent::save();
    }
}
