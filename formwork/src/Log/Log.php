<?php

namespace Formwork\Log;

trigger_error(sprintf('%s is deprecated since Formwork 2.3.0. Use the new %s instead', Log::class, Logger::class), E_USER_DEPRECATED);

/**
 * @deprecated since Formwork 2.3.0. Use `Formwork\Log\Logger` instead
 */
class Log extends Registry
{
    public function __construct(
        string $filename,
        protected int $limit = 128,
    ) {
        parent::__construct($filename);
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

    public function save(): void
    {
        if (count($this->storage) > $this->limit) {
            $this->storage = array_slice($this->storage, -$this->limit, null, true);
        }
        parent::save();
    }
}
