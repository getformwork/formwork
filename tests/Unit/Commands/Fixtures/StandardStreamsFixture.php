<?php

namespace Formwork\Tests\Unit\Commands\Fixtures;

use php_user_filter;
use RuntimeException;

/**
 * Captures what is written to the standard output and error streams, as the default consoles of the commands do,
 * which bypasses output buffering
 */
final class StandardStreamsFixture extends php_user_filter
{
    private const string FILTER = 'formwork-tests.standard-streams';

    private static string $captured = '';

    /**
     * Run a callback capturing, without printing it, what it writes to the standard streams
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return array{T, string}
     */
    public static function capture(callable $callback): array
    {
        if (!in_array(self::FILTER, stream_get_filters(), true)) {
            stream_filter_register(self::FILTER, self::class);
        }

        self::$captured = '';

        $filters = [];

        try {
            foreach ([\STDOUT, \STDERR] as $stream) {
                $filters[] = stream_filter_append($stream, self::FILTER, STREAM_FILTER_WRITE) ?: throw new RuntimeException('Cannot capture the standard streams');
            }

            $result = $callback();
        } finally {
            // The filters are always removed, as they would capture the output of the following tests too
            foreach ($filters as $filter) {
                stream_filter_remove($filter);
            }
        }

        return [$result, self::$captured];
    }

    public function filter($in, $out, &$consumed, bool $closing): int
    {
        while (($bucket = stream_bucket_make_writeable($in)) !== null) {
            self::$captured .= $bucket->data;
            $consumed += $bucket->datalen;
        }

        // No bucket is appended to the output, so nothing is printed
        return PSFS_PASS_ON;
    }
}
