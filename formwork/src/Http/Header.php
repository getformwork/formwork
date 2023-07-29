<?php

namespace Formwork\Http;

use Formwork\Traits\StaticClass;
use UnexpectedValueException;

class Header
{
    use StaticClass;

    // for=1;proto=2, for=1212;proto=343, for=12;proto
    // [[['for', 1], ['proto', 2], [['for', 1212], ['proto', 343]], [['for', 12], ['proto', true]]];

    public static function split(string $header, string $separators): array
    {
        $pattern = '/"[^"]*"(*SKIP)(*F)|' . preg_quote($separators[0], '/') . '/';

        return array_reduce(preg_split($pattern, $header), function ($result, $token) use ($separators) {
            $token = trim($token, ' "');
            $result[] = strlen($separators) === 1 ? $token : static::split($token, substr($separators, 1));
            return $result;
        }, []);
    }

    // [['for', 1], ['proto', 122], ['moo']]
    // ['for' => 1, 'proto => 122, 'moo' => true]
    public static function combine(array $tokens): array
    {
        return array_reduce($tokens, function ($result, $token) {
            if (count($token) === 0) {
                throw new UnexpectedValueException('Unexpected token format');
            }

            [$key, $value] = $token + [null, true];

            $result[$key] = $value;

            return $result;
        }, []);
    }

    public static function parseQualityValues(string $header): array
    {
        $result = [];
        foreach (explode(',', $header) as $token) {
            [$value, $factor] = preg_split('/\s*;\s*q=/', trim($token)) + [null, 1];
            $result[$value] = round($factor, 3);
        }
        arsort($result);
        return $result;
    }
}
