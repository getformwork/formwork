<?php

namespace Formwork\Tests\Unit\Parsers\Fixtures;

use Formwork\Parsers\AbstractEncoder;

final class EchoEncoder extends AbstractEncoder
{
    /**
     * @param array<string, mixed> $options
     */
    public static function parse(string $input, array $options = []): string
    {
        return $input;
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function encode(mixed $data, array $options = []): string
    {
        return 'encoded:' . json_encode($data) . ':' . json_encode($options);
    }
}
