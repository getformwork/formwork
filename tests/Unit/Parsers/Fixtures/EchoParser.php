<?php

namespace Formwork\Tests\Unit\Parsers\Fixtures;

use Formwork\Parsers\AbstractParser;

final class EchoParser extends AbstractParser
{
    /**
     * @param array<string, mixed> $options
     *
     * @return array{string, array<string, mixed>}
     */
    public static function parse(string $input, array $options = []): array
    {
        return [$input, $options];
    }
}
