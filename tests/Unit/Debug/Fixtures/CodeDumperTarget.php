<?php

namespace Formwork\Tests\Unit\Debug\Fixtures;

class CodeDumperTarget
{
    public static function describe(string $name, int $count = 3, string ...$rest): void {}

    public function method(#[\SensitiveParameter] string $secret, array $options = []): void {}
}

function code_dumper_function(string $first, $second = 'default'): void {}
