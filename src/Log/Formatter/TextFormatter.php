<?php

namespace Formwork\Log\Formatter;

use DateTimeInterface;
use Formwork\Parsers\Json;

/**
 * @since 2.3.0
 */
class TextFormatter extends AbstractFormatter
{
    public const DATE_FORMAT = 'Y-m-d H:i:s.u';

    public function __construct(
        private bool $withContext = true
    ) {}

    /**
     * @inheritDoc
     */
    public function format(DateTimeInterface $datetime, string $level, string $message, array $context): string
    {
        return sprintf(
            '[%s] %s: %s%s',
            $datetime->format(self::DATE_FORMAT),
            strtoupper($level),
            $this->interpolate($message, $context, self::DATE_FORMAT),
            ($this->withContext && $context !== []) ? ' ' . Json::encode($this->normalize($context, self::DATE_FORMAT)) : ''
        );
    }
}
