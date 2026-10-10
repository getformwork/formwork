<?php

namespace Formwork\Tests\Unit\Log\Fixtures;

use DateTimeInterface;
use Formwork\Log\Formatter\AbstractFormatter;

final class ExposedFormatter extends AbstractFormatter
{
    public function format(DateTimeInterface $datetime, string $level, string $message, array $context): string
    {
        return $this->interpolate($message, $context);
    }

    /**
     * @param array<mixed> $context
     */
    public function interpolateMessage(string $message, array $context, string $dateFormat = DateTimeInterface::RFC3339): string
    {
        return $this->interpolate($message, $context, $dateFormat);
    }

    public function normalizeData(mixed $data, string $dateFormat = DateTimeInterface::RFC3339, int $depth = 4): mixed
    {
        return $this->normalize($data, $dateFormat, $depth);
    }
}
