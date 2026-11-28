<?php

namespace Formwork\Log;

use Formwork\Log\Handler\HandlerInterface;

/**
 * @since 2.4.0
 */
class LoggerManager
{
    /**
     * @var array<non-empty-string, Logger>
     */
    protected array $loggers = [];

    /**
     * @param list<HandlerInterface> $handlers
     */
    public function __construct(protected array $handlers = []) {}

    /**
     * Get the logger of a channel, creating it if it does not exist yet
     *
     * @param non-empty-string $channel
     */
    public function get(string $channel): Logger
    {
        if (!isset($this->loggers[$channel])) {
            $logger = new Logger($channel);
            foreach ($this->handlers as $handler) {
                $logger->addHandler($handler);
            }
            $this->loggers[$channel] = $logger;
        }

        return $this->loggers[$channel];
    }

    /**
     * Add a handler to the manager and to the loggers already created
     *
     * Handlers decide by themselves which channels they handle.
     */
    public function addHandler(HandlerInterface $handler): void
    {
        $this->handlers[] = $handler;

        foreach ($this->loggers as $logger) {
            $logger->addHandler($handler);
        }
    }
}
