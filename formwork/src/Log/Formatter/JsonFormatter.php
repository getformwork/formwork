<?php

namespace Formwork\Log\Formatter;

use DateTimeInterface;

/**
 * @since 2.3.0
 */
class JsonFormatter extends AbstractFormatter
{
    public const DATE_FORMAT = 'Y-m-d\TH:i:s.uP';

    /**
     * @inheritDoc
     */
    public function format(DateTimeInterface $datetime, string $level, string $message, array $context): string
    {
        return json_encode([
            'datetime' => $datetime->format(self::DATE_FORMAT),
            'level'    => $level,
            'message'  => $this->interpolate($message, $context, self::DATE_FORMAT),
            'context'  => $this->normalize($context, self::DATE_FORMAT),
        ], JSON_THROW_ON_ERROR | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
