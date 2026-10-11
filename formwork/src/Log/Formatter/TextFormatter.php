<?php

namespace Formwork\Log\Formatter;

use DateTimeInterface;

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
            '[%s] %s: %s',
            $datetime->format(self::DATE_FORMAT),
            strtoupper($level),
            rtrim(implode(' ', [
                $this->interpolate($message, $context, self::DATE_FORMAT),
                $this->formatContext($this->withContext ? $context : [], self::DATE_FORMAT),
            ])),
        );
    }

    /**
     * Format the context array as JSON or empty string if the context is empty
     *
     * @param array<mixed> $context
     */
    protected function formatContext(array $context, string $dateFormat): string
    {
        if ($context === []) {
            return '';
        }

        return json_encode(
            $this->normalize($context, $dateFormat),
            JSON_THROW_ON_ERROR | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
}
