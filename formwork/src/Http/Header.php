<?php

namespace Formwork\Http;

use Formwork\Traits\StaticClass;
use RuntimeException;
use UnexpectedValueException;

class Header
{
    use StaticClass;

    // for=1;proto=2, for=1212;proto=343, for=12;proto
    // [[['for', 1], ['proto', 2], [['for', 1212], ['proto', 343]], [['for', 12], ['proto', true]]];
    /**
     * @return list<mixed>
     */
    public static function split(string $header, string $separators): array
    {
        $pattern = '/"[^"]*"(*SKIP)(*F)|' . preg_quote($separators[0], '/') . '/';

        if (($tokens = preg_split($pattern, $header)) === false) {
            throw new RuntimeException(sprintf('Header splitting failed with error: %s', preg_last_error_msg()));
        }

        return array_reduce($tokens, function ($result, $token) use ($separators) {
            $token = trim($token, ' "');
            $result[] = strlen($separators) === 1 ? $token : static::split($token, substr($separators, 1));
            return $result;
        }, []);
    }

    // [['for', 1], ['proto', 122], ['moo']]
    // ['for' => 1, 'proto => 122, 'moo' => true]
    /**
     * @param list<mixed> $tokens
     *
     * @return array<string, mixed>
     */
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

    /**
     * @return array<float>
     */
    public static function parseQualityValues(string $header): array
    {
        $result = [];
        foreach (explode(',', $header) as $token) {
            if (($valueAndFactor = preg_split('/\s*;\s*q=/', trim($token))) === false) {
                throw new UnexpectedValueException('Cannot parse quality value and factor');
            }
            [$value, $factor] = $valueAndFactor + ['', 1.0];
            $result[$value] = round((float) $factor, 3);
        }
        arsort($result);
        return $result;
    }
}
