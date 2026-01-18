<?php

namespace Formwork\Log;

use DateTimeImmutable;
use Formwork\Log\Handler\HandlerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

/**
 * @since 2.3.0
 */
class Logger extends AbstractLogger implements LoggerInterface
{
    public const LOG_LEVELS = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT     => 1,
        LogLevel::CRITICAL  => 2,
        LogLevel::ERROR     => 3,
        LogLevel::WARNING   => 4,
        LogLevel::NOTICE    => 5,
        LogLevel::INFO      => 6,
        LogLevel::DEBUG     => 7,
    ];

    /**
     * @var array<HandlerInterface>
     */
    private $handlers = [];

    /**
     * Add a log handler
     */
    public function addHandler(HandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }

    /**
     * Log with an arbitrary level
     *
     * @param LogLevel::* $level
     */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        // @phpstan-ignore isset.offset
        if (!isset(self::LOG_LEVELS[$level])) {
            throw new InvalidArgumentException(sprintf('Invalid log level: "%s"', (string) $level));
        }

        $datetime = new DateTimeImmutable(sprintf('@%.6F', microtime(true)));

        foreach ($this->handlers as $handler) {
            $handler->handle(
                $datetime,
                (string) $level,
                (string) $message,
                $context
            );
        }
    }
}
