<?php

namespace Formwork\Log\Handler;

use DateTimeInterface;
use Formwork\Log\Formatter\FormatterInterface;
use Formwork\Log\Logger;
use InvalidArgumentException;
use Psr\Log\LogLevel;

abstract class AbstractHandler implements HandlerInterface
{
    /**
     * @param LogLevel::* $level
     */
    public function __construct(
        protected FormatterInterface $formatter,
        protected string $level = LogLevel::DEBUG,
    ) {
        // @phpstan-ignore isset.offset
        if (!isset(Logger::LOG_LEVELS[$level])) {
            throw new InvalidArgumentException(sprintf('Invalid log level: "%s"', $level));
        }
    }

    /**
     * @inheritDoc
     */
    abstract public function handle(DateTimeInterface $datetime, string $level, string $message, array $context): void;

    /**
     * Determine if the handler should handle a log record with the given level
     *
     * @param LogLevel::* $level
     */
    protected function shouldHandle(string $level): bool
    {
        // @phpstan-ignore isset.offset
        if (!isset(Logger::LOG_LEVELS[$level])) {
            throw new InvalidArgumentException(sprintf('Invalid log level: "%s"', $level));
        }
        return Logger::LOG_LEVELS[$level] <= Logger::LOG_LEVELS[$this->level];
    }
}
