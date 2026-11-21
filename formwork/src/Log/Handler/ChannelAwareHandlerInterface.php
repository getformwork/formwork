<?php

namespace Formwork\Log\Handler;

use DateTimeInterface;
use Formwork\Log\Logger;
use Psr\Log\LogLevel;

/**
 * Handler that also receives the channel of the log entries
 *
 * @since 2.4.0
 */
interface ChannelAwareHandlerInterface extends HandlerInterface
{
    /**
     * Handle a log entry of the given channel
     *
     * @param LogLevel::*  $level
     * @param array<mixed> $context
     */
    public function handle(DateTimeInterface $datetime, string $level, string $message, array $context, string $channel = Logger::DEFAULT_CHANNEL): void;
}
