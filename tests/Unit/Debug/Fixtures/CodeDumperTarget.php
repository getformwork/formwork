<?php

namespace Formwork\Tests\Unit\Debug\Fixtures;

use SensitiveParameter;

class CodeDumperTarget
{
    public static function describe(string $name, int $count = 3, string ...$rest): void {}

    /**
     * @param array<string, string> $options
     */
    public function method(#[SensitiveParameter] string $secret, array $options = []): void {}
}

/**
 * @param string $second
 */
function code_dumper_function(string $first, $second = 'default'): void {}
