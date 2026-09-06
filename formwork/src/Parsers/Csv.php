<?php

namespace Formwork\Parsers;

use Formwork\Utils\Arr;
use InvalidArgumentException;
use RuntimeException;

class Csv extends AbstractEncoder
{
    public const SEPARATOR_COMMA = ',';

    public const SEPARATOR_SEMICOLON = ';';

    public const SEPARATOR_TAB = "\t";

    public const ENCLOSURE_DOUBLE_QUOTE = '"';

    public const ENCLOSURE_SINGLE_QUOTE = "'";

    private const array SUPPORTED_SEPARATORS = [
        self::SEPARATOR_COMMA,
        self::SEPARATOR_SEMICOLON,
        self::SEPARATOR_TAB,
    ];

    private const array SUPPORTED_ENCLOSURES = [
        self::ENCLOSURE_DOUBLE_QUOTE,
        self::ENCLOSURE_SINGLE_QUOTE,
    ];

    /**
     * Default options used to parse CSV
     *
     * @var array{separator: self::SEPARATOR_*, enclosure: self::ENCLOSURE_*}
     */
    private const array DEFAULT_OPTIONS = [
        'separator' => self::SEPARATOR_COMMA,
        'enclosure' => self::ENCLOSURE_DOUBLE_QUOTE,
    ];

    /**
     * Parse a CSV string
     *
     * @param array{separator?: string, enclosure?: string} $options
     *
     * @return list<list<string|null>>
     */
    public static function parse(string $input, array $options = []): array
    {
        $options = [...self::DEFAULT_OPTIONS, ...$options];

        if (!in_array($options['separator'], self::SUPPORTED_SEPARATORS, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported CSV separator "%s"', $options['separator']));
        }

        if (!in_array($options['enclosure'], self::SUPPORTED_ENCLOSURES, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported CSV enclosure "%s"', $options['enclosure']));
        }

        if (($stream = fopen('php://temp', 'w+')) === false) {
            throw new RuntimeException('Failed to open temporary stream for CSV parsing');
        }

        try {
            fwrite($stream, $input);
            rewind($stream);

            $data = [];

            while (($row = fgetcsv($stream, null, $options['separator'], $options['enclosure'], '')) !== false) {
                if ($row === [null]) {
                    continue;
                }
                $data[] = $row;
            }

            return $data;
        } finally {
            fclose($stream);
        }
    }

    /**
     * Encode data to CSV format
     *
     * @param array{separator?: string, enclosure?: string} $options
     */
    public static function encode(mixed $data, array $options = []): string
    {
        $options = [...self::DEFAULT_OPTIONS, ...$options];

        if (!in_array($options['separator'], self::SUPPORTED_SEPARATORS, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported CSV separator "%s"', $options['separator']));
        }

        if (!in_array($options['enclosure'], self::SUPPORTED_ENCLOSURES, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported CSV enclosure "%s"', $options['enclosure']));
        }

        if (!is_array($data) || Arr::some($data, fn($record) => !is_array($record))) {
            throw new RuntimeException('Data must be an array of arrays to encode as CSV');
        }

        if (($stream = fopen('php://temp', 'w')) === false) {
            throw new RuntimeException('Failed to open temporary stream for CSV encoding');
        }

        try {
            foreach ($data as $row) {
                fputcsv($stream, $row, $options['separator'], $options['enclosure'], '');
            }

            return stream_get_contents($stream, offset: 0);
        } finally {
            fclose($stream);
        }
    }
}
