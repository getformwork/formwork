<?php

namespace Formwork\Log\Handler;

use Formwork\Log\Formatter\FormatterInterface;
use Formwork\Log\Formatter\TextFormatter;
use Psr\Log\LogLevel;

/**
 * @inheritDoc
 */
class StderrHandler extends FileHandler
{
    public function __construct(
        FormatterInterface $formatter = new TextFormatter(),
        string $level = LogLevel::DEBUG,
    ) {
        parent::__construct('php://stderr', $formatter, $level);
    }
}
