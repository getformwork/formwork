<?php

namespace Formwork\Log\Formatter;

use DateTimeInterface;
use Formwork\Log\Logger;
use Psr\Log\LogLevel;

/**
 * Formatter that also includes the channel of the log entries
 *
 * @since 2.4.0
 */
interface ChannelAwareFormatterInterface extends FormatterInterface
{
    /**
     * Format a log entry of the given channel
     *
     * @param LogLevel::*  $level
     * @param array<mixed> $context
     */
    public function format(DateTimeInterface $datetime, string $level, string $message, array $context, string $channel = Logger::DEFAULT_CHANNEL): string;
}
