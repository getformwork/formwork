<?php

namespace Formwork\Log\Formatter;

use BackedEnum;
use DateTimeInterface;
use Formwork\Data\Contracts\ArraySerializable;
use Formwork\Utils\Arr;
use Formwork\Utils\Constraint;
use JsonSerializable;
use Stringable;
use Throwable;
use UnitEnum;

/**
 * @since 2.3.0
 */
abstract class AbstractFormatter implements FormatterInterface
{
    abstract public function format(DateTimeInterface $datetime, string $level, string $message, array $context): string;

    /**
     * Interpolate context values into the message placeholders
     *
     * @param array<mixed> $context
     */
    protected function interpolate(string $message, array $context = [], string $dateFormat = DateTimeInterface::RFC3339): string
    {
        $replacements = [];

        foreach ($context as $key => $value) {
            if (is_string($key) && !Constraint::matchesRegex($key, '/^[A-Za-z0-9_.]+$/')) {
                continue;
            }

            $placeholder = '{' . $key . '}';

            if ($value === null || is_scalar($value) || $value instanceof Stringable) {
                $replacements[$placeholder] = (string) $value;
            } elseif ($value instanceof DateTimeInterface) {
                $replacements[$placeholder] = $value->format($dateFormat);
            } elseif ($value instanceof UnitEnum) {
                $replacements[$placeholder] = $value instanceof BackedEnum ? $value->value : $value->name;
            } else {
                $type = get_debug_type($value);

                if (is_object($value)) {
                    $type = "object {$type}";
                }

                $replacements[$placeholder] = "[$type]";
            }
        }

        return strtr($message, $replacements);
    }

    /**
     * Normalize data for logging
     */
    protected function normalize(mixed $data, string $dateFormat = DateTimeInterface::RFC3339, int $depth = 4): mixed
    {
        if ($data === null || is_scalar($data)) {
            return $data;
        }

        if ($depth === 0) {
            if (is_array($data)) {
                return '[array]';
            }

            if (is_object($data)) {
                $type = get_debug_type($data);
                return "[object {$type}]";
            }
        }

        if (is_array($data)) {
            return Arr::map($data, fn($value) => $this->normalize($value, $dateFormat, $depth - 1));
        }

        $type = get_debug_type($data);

        if (is_object($data)) {
            if ($data instanceof DateTimeInterface) {
                return $data->format($dateFormat);
            }

            if ($data instanceof Throwable) {
                $trace = $data->getTrace();
                $previous = $data->getPrevious();

                $data = [
                    'class'   => $type,
                    'message' => $data->getMessage(),
                    'code'    => (int) $data->getCode(),
                    'file'    => "{$data->getFile()}:{$data->getLine()}",
                    'trace'   => [],
                ];

                foreach ($trace as $frame) {
                    if (isset($frame['file'], $frame['line'])) {
                        $data['trace'][] = "{$frame['file']}:{$frame['line']}";
                    }
                }

                if ($previous !== null) {
                    $data['previous'] = $this->normalize($previous, $dateFormat, $depth - 1);
                }

                return $data;
            }

            if ($data instanceof Stringable) {
                $data = (string) $data;
            }

            if ($data instanceof JsonSerializable) {
                $data = $data->jsonSerialize();
            }

            if ($data instanceof ArraySerializable) {
                $data = $data->toArray();
            }

            return [$type => $data];
        }

        if (is_resource($data)) {
            return "[$type]";
        }

        return "[unknown {$type}]";
    }
}
