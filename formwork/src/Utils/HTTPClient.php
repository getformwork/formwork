<?php

namespace Formwork\Utils;

use Formwork\Formwork;
use Formwork\Response\Response;
use Formwork\Utils\Exceptions\ConnectionException;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class HTTPClient
{
    /**
     * Default Formwork user agent
     */
    protected const DEFAULT_USER_AGENT = 'PHP Formwork/' . Formwork::VERSION;

    /**
     * Regex matching HTTP status line
     */
    protected const STATUS_LINE_REGEX = '~^(HTTP/\d+\.\d+)\s+(\d+)\s+(.+)~i';

    /**
     * Client options
     */
    protected array $options = [];

    public function __construct(array $options = [])
    {
        $this->options = array_replace_recursive($this->defaults(), $options);
    }

    /**
     * Default client options
     */
    public function defaults(): array
    {
        return [
            'version'   => 1.1,
            'method'    => 'GET',
            'timeout'   => -1,
            'headers'   => ['User-Agent' => ini_get('user_agent') ?: self::DEFAULT_USER_AGENT],
            'content'   => '',
            'redirects' => ['follow' => true, 'limit' => 5],
            'ssl'       => ['verify' => true, 'cabundle' => null]
        ];
    }

    /**
     * Fetch contents from a URI
     */
    public function fetch(string $uri, array $options = []): Response
    {
        $connection = $this->connect($uri, $options);

        if (($content = @stream_get_contents($connection['handle'], $connection['length'] ?? -1)) === false) {
            throw new RuntimeException(sprintf('Cannot get stream contents from "%s"', $uri));
        }

        @fclose($connection['handle']);

        return new Response($content, $connection['status'], $connection['headers']);
    }

    public function fetchHeaders(string $uri, array $options = []): array
    {
        $options += [
            'method' => 'HEAD'
        ];

        return $this->fetch($uri, $options)->headers();
    }

    /**
     * Download contents from an URI to a file
     */
    public function download(string $uri, string $file, array $options = []): void
    {
        $connection = $this->connect($uri, $options);

        if (($destination = @fopen($file, 'w')) === false) {
            throw new RuntimeException(sprintf('Cannot open destination "%s" for writing', $file));
        }

        if (@stream_copy_to_stream($connection['handle'], $destination, $connection['length'] ?? -1) === false) {
            throw new RuntimeException(sprintf('Cannot copy stream contents from "%s" to "%s"', $uri, $file));
        }

        @fclose($destination);

        @fclose($connection['handle']);
    }

    /**
     * Connect to URI and retrieve status, headers, length and stream handle
     */
    protected function connect(string $uri, array $options = []): array
    {
        if (filter_var($uri, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException(sprintf('Cannot connect to "%s": invalid URI', $uri));
        }

        $options = array_replace_recursive($this->options, $options);

        $options['headers'] = $this->normalizeHeaders($options['headers']);

        // If no `Connection` header is given, we add an explicit `Connection: close`
        // for HTTP/1.1 requests. Otherwise, if the response has no `Content-Length`,
        // the request will hang until the timeout is reached
        if ((float) $options['version'] === 1.1 && !isset($options['headers']['Connection'])) {
            $options['headers']['Connection'] = 'close';
        }

        $context = $this->createContext($options);

        $errors = [];

        set_error_handler(static function (int $severity, string $message, string $file, int $line) use (&$errors): bool {
            $errors[] = compact('severity', 'message', 'file', 'line');
            return true;
        });

        if (($handle = @fopen($uri, 'r', false, $context)) === false) {
            $messages = implode("\n", array_map(
                static function (int $i, array $error): string {
                    return sprintf('#%d %s', $i, str_replace("\n", ' ', $error['message']));
                },
                array_keys($errors),
                $errors
            ));

            throw new ConnectionException(sprintf("Cannot connect to \"%s\". Error messages:\n%s", $uri, $messages));
        }

        restore_error_handler();

        if (!isset($http_response_header)) {
            throw new RuntimeException(sprintf('Cannot get headers for "%s"', $uri));
        }

        $splitResponse = $this->splitHTTPResponseHeader($http_response_header);

        $currentResponse = end($splitResponse);

        $length = $currentResponse['headers']['Content-Length'] ?? null;

        if (strtoupper($options['method']) === 'HEAD') {
            $length = 0;
        }

        return [
            'status' => $currentResponse['statusCode'],
            'headers'=> $currentResponse['headers'],
            'length' => $length,
            'handle' => $handle
        ];
    }

    /**
     * Create stream context
     *
     * @return resource
     */
    protected function createContext(array $options)
    {
        $contextOptions = [
            'http' => [
                'protocol_version' => $options['version'],
                'method'           => $options['method'],
                'header'           => $this->compactHeaders($options['headers']),
                'content'          => $options['content'],
                'follow_location'  => $options['redirects']['follow'] ? 1 : 0,
                'max_redirects'    => $options['redirects']['limit'],
                'timeout'          => $options['timeout'],
                'ignore_errors'    => true
            ],
            'ssl' => [
                'verify_peer'       => $options['ssl']['verify'],
                'verify_peer_name'  => $options['ssl']['verify'],
                'allow_self_signed' => false
            ]
        ];

        if (($bundle = $options['ssl']['cabundle']) !== null) {
            if (!FileSystem::isReadable($bundle)) {
                throw new RuntimeException('The given CA bundle is not readable');
            }
            $key = FileSystem::isFile($bundle) ? 'cafile' : 'capath';
            $contextOptions['ssl'][$key] = $bundle;
        }

        return stream_context_create($contextOptions);
    }

    /**
     * Split HTTP response header lines
     */
    protected function splitHTTPResponseHeader(array $lines): array
    {
        $i = -1;
        $result = [];
        foreach ($lines as $line) {
            if (preg_match(self::STATUS_LINE_REGEX, $line, $matches)) {
                $i++;
                $result[$i]['HTTPVersion'] = $matches[1];
                $result[$i]['statusCode'] = (int) $matches[2];
                $result[$i]['reasonPhrase'] = $matches[3];
            } elseif ($i < 0) {
                throw new UnexpectedValueException('Unexpected header field: headers must come after an HTTP status line');
            } else {
                $this->splitHeader($line, $result[$i]['headers']);
            }
        }
        return $result;
    }

    /**
     * Split header contents into a target array
     */
    protected function splitHeader(string $header, ?array &$target): void
    {
        $parts = explode(':', $header, 2);
        $key = ucwords(strtolower(trim($parts[0])), '-');
        $value = isset($parts[1]) ? trim($parts[1]) : null;

        if (isset($target[$key])) {
            if (!is_array($target[$key])) {
                $target[$key] = [$target[$key]];
            }
            $target[$key][] = $value;
        } else {
            $target[$key] = $value;
        }
    }

    /**
     * Normalize header keys case
     */
    protected function normalizeHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $key => $value) {
            $key = ucwords(strtolower($key), '-');
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * Compact an associative array of headers to an array of header lines
     */
    protected function compactHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $key => $value) {
            $key = trim($key);
            if (is_array($value)) {
                foreach ($value as $v) {
                    $result[] = sprintf('%s: %s', $key, trim($v));
                }
            } else {
                $result[] = sprintf('%s: %s', $key, trim($value));
            }
        }
        return $result;
    }
}
