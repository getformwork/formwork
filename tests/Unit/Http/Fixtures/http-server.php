<?php

/**
 * Minimal HTTP server used by the `Client` tests
 *
 * It listens on a random local port, prints the port on the standard output
 * and answers each connection with a canned response selected by the request path.
 */

$server = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);

if ($server === false) {
    fwrite(STDERR, "Cannot start the server: {$errorMessage}\n");
    exit(1);
}

fwrite(STDOUT, substr((string) strrchr((string) stream_socket_get_name($server, false), ':'), 1) . "\n");
fflush(STDOUT);

while (($connection = @stream_socket_accept($server, -1)) !== false) {
    $head = '';

    while (($line = fgets($connection)) !== false) {
        $head .= $line;

        if (rtrim($line, "\r\n") === '') {
            break;
        }
    }

    $lines = explode("\r\n", rtrim($head));
    [$method, $target] = explode(' ', array_shift($lines)) + [1 => '/'];
    $path = parse_url($target, PHP_URL_PATH);

    $headers = [];

    foreach ($lines as $line) {
        if (str_contains($line, ':')) {
            [$name, $value] = explode(':', $line, 2);
            $headers[ucwords(strtolower(trim($name)), '-')] = trim($value);
        }
    }

    $body = isset($headers['Content-Length']) ? (string) fread($connection, (int) $headers['Content-Length']) : '';

    $respond = static function (int $status, string $reason, array $responseHeaders, string $content, ?int $length = null) use ($connection, $method): void {
        $responseHeaders += ['Content-Length' => (string) ($length ?? strlen($content))];
        $response = "HTTP/1.1 {$status} {$reason}\r\n";

        foreach ($responseHeaders as $name => $value) {
            $response .= "{$name}: {$value}\r\n";
        }

        fwrite($connection, $response . "\r\n" . ($method === 'HEAD' ? '' : $content));
    };

    switch ($path) {
        case '/ok':
            $respond(200, 'OK', ['Content-Type' => 'text/plain', 'X-Custom' => 'value'], 'hello');
            break;

        case '/echo':
            $respond(200, 'OK', ['Content-Type' => 'application/json'], (string) json_encode(compact('method', 'headers', 'body')));
            break;

        case '/gzip':
            $respond(200, 'OK', ['Content-Encoding' => 'gzip'], (string) gzencode('compressed content'));
            break;

        case '/bad-gzip':
            $respond(200, 'OK', ['Content-Encoding' => 'gzip'], 'this is not gzip');
            break;

        case '/deflate':
            $respond(200, 'OK', ['Content-Encoding' => 'deflate'], (string) gzdeflate('content'));
            break;

        case '/redirect':
            $respond(302, 'Found', ['Location' => '/ok'], '');
            break;

        case '/loop':
            $respond(302, 'Found', ['Location' => '/loop'], '');
            break;

        case '/missing':
            $respond(404, 'Not Found', ['Content-Type' => 'text/plain'], 'nope');
            break;

        case '/truncated':
            $respond(200, 'OK', [], 'short', 10);
            break;

        default:
            $respond(500, 'Internal Server Error', [], 'unexpected path ' . $path);
    }

    fclose($connection);
}
