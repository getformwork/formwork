<?php

namespace Formwork\Http;

use Formwork\Cms\App;
use Formwork\Http\Exceptions\ConnectionException;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class Client
{
    /**
     * Default Formwork user agent
     */
    protected const string DEFAULT_USER_AGENT = 'PHP Formwork/' . App::VERSION;

    /**
     * Regex matching HTTP status line
     */
    protected const string STATUS_LINE_REGEX = '~^(HTTP/\d+\.\d+)\s+(\d+)\s+(.+)~i';

    /**
     * Client options
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        if (!extension_loaded('openssl')) {
            throw new RuntimeException(sprintf('Class %s requires the extension "openssl" to be enabled', static::class));
        }

        if (ini_get('allow_url_fopen') !== '1') {
            throw new RuntimeException(sprintf('Class %s requires "allow_url_fopen" to be enabled in PHP configuration', static::class));
        }

        $this->options = array_replace_recursive($this->defaults(), $options);
    }

    /**
     * Default client options
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'version'   => 1.1,
            'method'    => 'GET',
            'timeout'   => -1,
            'headers'   => ['User-Agent' => ini_get('user_agent') ?: self::DEFAULT_USER_AGENT, 'Accept-Encoding' => 'gzip, deflate'],
            'content'   => '',
            'redirects' => ['follow' => true, 'limit' => 5],
            'ssl'       => ['verify' => true, 'cabundle' => null],
        ];
    }

    /**
     * Fetch contents from a URI
     *
     * @param array<string, mixed> $options
     */
    public function fetch(string $uri, array $options = []): Response
    {
        $connection = $this->connect($uri, $options);

        $content = @stream_get_contents($connection['handle'], $connection['length'] ?? -1);

        if ($content === false) {
            throw new RuntimeException(sprintf('Cannot get stream contents from "%s"', $uri));
        }

        @fclose($connection['handle']);

        if (($connection['headers']['Content-Encoding'] ?? null) === 'gzip') {
            $content = gzdecode($content);
            if ($content === false) {
                throw new RuntimeException(sprintf('Cannot decode gzipped contents from "%s"', $uri));
            }
        }

        return new Response($content, ResponseStatus::fromCode($connection['status']), $connection['headers']);
    }

    /**
     * Fetch headers from a URI
     *
     * @param array<string, mixed> $options
     */
    public function fetchHeaders(string $uri, array $options = []): ResponseHeaders
    {
        $options += [
            'method' => 'HEAD',
        ];

        return $this->fetch($uri, $options)->headers();
    }

    /**
     * Download contents from an URI to a file
     *
     * @param array<string, mixed> $options
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
     *
     * @param array<string, mixed> $options
     *
     * @return array{status: int, headers: array<string, string>, length: int|null, handle: resource}
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
                static fn(int $i, array $error): string => sprintf('#%d %s', $i, str_replace("\n", ' ', $error['message'])),
                array_keys($errors),
                $errors
            ));

            throw new ConnectionException(sprintf("Cannot connect to \"%s\". Error messages:\n%s", $uri, $messages));
        }

        restore_error_handler();

        if (!@$http_response_header) {
            throw new RuntimeException(sprintf('Cannot get headers for "%s"', $uri));
        }

        $splitResponse = $this->splitHTTPResponseHeader($http_response_header);

        $currentResponse = end($splitResponse);

        if ($currentResponse === false) {
            throw new RuntimeException(sprintf('Cannot get current response for "%s"', $uri));
        }

        $length = $currentResponse['headers']['Content-Length'] ?? null;

        if (strtoupper((string) $options['method']) === 'HEAD') {
            $length = 0;
        }

        //TODO
        return [
            'status'  => $currentResponse['statusCode'],
            'headers' => $currentResponse['headers'],
            'length'  => $length !== null ? (int) $length : null,
            'handle'  => $handle,
        ];
    }

    /**
     * Create stream context
     *
     * @param array<string, mixed> $options
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
                'ignore_errors'    => true,
            ],
            'ssl' => [
                'verify_peer'       => $options['ssl']['verify'],
                'verify_peer_name'  => $options['ssl']['verify'],
                'allow_self_signed' => false,
            ],
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
     *
     * @param array<string> $lines
     *
     * @return array<array{HTTPVersion: string, statusCode: int, reasonPhrase: string, headers: array<string, string>}>
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
                $result[$i]['headers'] = [];
            } elseif ($i < 0 || !isset($result[$i])) {
                throw new UnexpectedValueException('Unexpected header field: headers must come after an HTTP status line');
            } else {
                $this->splitHeader($line, $result[$i]['headers']);
            }
        }
        return $result;
    }

    /**
     * Split header contents into a target array
     *
     * @param array<string, string> $target
     */
    protected function splitHeader(string $header, array &$target): void
    {
        $parts = explode(':', $header, 2);
        $key = ucwords(strtolower(trim($parts[0])), '-');
        $value = isset($parts[1]) ? trim($parts[1]) : '';

        $target[$key] = $value;
    }

    /**
     * Normalize header keys case
     *
     * @param array<string, string> $headers
     *
     * @return array<string, string>
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
     *
     * @param array<string, list<string>|string> $headers
     *
     * @return list<string>
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
