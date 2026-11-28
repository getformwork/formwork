<?php

namespace Formwork\Log\Formatter;

use DateTimeInterface;
use Formwork\Log\Logger;

/**
 * @since 2.3.0
 */
class JsonFormatter extends AbstractFormatter implements ChannelAwareFormatterInterface
{
    public const DATE_FORMAT = 'Y-m-d\TH:i:s.uP';

    /**
     * @inheritDoc
     */
    public function format(DateTimeInterface $datetime, string $level, string $message, array $context, string $channel = Logger::DEFAULT_CHANNEL): string
    {
        return (string) json_encode([
            'datetime' => $datetime->format(self::DATE_FORMAT),
            'channel'  => $channel,
            'level'    => $level,
            'message'  => $this->interpolate($message, $context, self::DATE_FORMAT),
            'context'  => $this->normalize($context, self::DATE_FORMAT),
        ], JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
