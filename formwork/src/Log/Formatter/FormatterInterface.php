<?php

namespace Formwork\Log\Formatter;

use DateTimeInterface;
use Psr\Log\LogLevel;

interface FormatterInterface
{
    /**
     * Format a log entry
     *
     * @param LogLevel::*  $level
     * @param array<mixed> $context
     */
    public function format(DateTimeInterface $datetime, string $level, string $message, array $context): string;
}
