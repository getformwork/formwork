<?php

namespace Formwork\Log\Handler;

use DateTimeInterface;
use Psr\Log\LogLevel;

interface HandlerInterface
{
    /**
     * Handle a log entry
     *
     * @param LogLevel::*  $level
     * @param array<mixed> $context
     */
    public function handle(DateTimeInterface $datetime, string $level, string $message, array $context): void;
}
