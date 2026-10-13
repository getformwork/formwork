<?php

namespace Formwork\Http\Transport;

use Formwork\Http\Exceptions\ConnectionException;
use RuntimeException;

class StreamHttpTransport implements HttpTransportInterface
{
    public function __construct()
    {
        if (!extension_loaded('openssl')) {
            throw new RuntimeException(sprintf('Class %s requires the extension "openssl" to be enabled', static::class));
        }

        if (ini_get('allow_url_fopen') !== '1') {
            throw new RuntimeException(sprintf('Class %s requires "allow_url_fopen" to be enabled in PHP configuration', static::class));
        }
    }

    public function open(string $uri, array $contextOptions): array
    {
        $context = stream_context_create($contextOptions);

        $errors = [];

        set_error_handler(static function (int $severity, string $message, string $file, int $line) use (&$errors): bool {
            $errors[] = compact('severity', 'message', 'file', 'line');
            return true;
        });

        try {
            if (($handle = @fopen($uri, 'r', false, $context)) === false) {
                $messages = implode("\n", array_map(
                    static fn(int $i, array $error): string => sprintf('#%d %s', $i, str_replace("\n", ' ', $error['message'])),
                    array_keys($errors),
                    $errors
                ));

                throw new ConnectionException(sprintf("Cannot connect to \"%s\". Error messages:\n%s", $uri, $messages));
            }
        } finally {
            restore_error_handler();
        }

        // @todo use the function directly when we drop support for PHP < 8.4
        if (function_exists('http_get_last_response_headers')) {
            $http_response_header = http_get_last_response_headers();
        }

        if (!isset($http_response_header) || $http_response_header === []) {
            throw new RuntimeException(sprintf('Cannot get headers for "%s"', $uri));
        }

        return ['handle' => $handle, 'headers' => $http_response_header];
    }
}
