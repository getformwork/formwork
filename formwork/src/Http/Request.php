<?php

namespace Formwork\Http;

use Formwork\Http\Files\UploadedFile;
use Formwork\Http\Session\Session;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use InvalidArgumentException;
use UnexpectedValueException;

class Request
{
    /**
     * Default ports for HTTP and HTTPS protocols
     *
     * @var array<string, int>
     */
    public const array DEFAULT_PORTS = ['http' => 80, 'https' => 443];

    /**
     * List of IP addresses considered as localhost
     *
     * @var list<string>
     */
    protected const array LOCALHOST_IP_ADDRESSES = ['127.0.0.1', '::1'];

    /**
     * List of forwarded directives
     *
     * @var list<string>
     */
    protected const array FORWARDED_DIRECTIVES = ['for', 'host', 'proto', 'port'];

    /**
     * Request input data. Corresponds to `$_POST`
     */
    protected RequestData $input;

    /**
     * Request query data. Corresponds to `$_GET`
     */
    protected RequestData $query;

    /**
     * Request cookies data. Corresponds to `$_COOKIE`
     */
    protected RequestData $cookies;

    /**
     * Request files data. Corresponds to a normalized version of `$_FILES`
     */
    protected FilesData $files;

    /**
     * Request server data. Corresponds to `$_SERVER`
     */
    protected ServerData $server;

    /**
     * Request headers data
     */
    protected HeadersData $headers;

    /**
     * Session associated with the request
     */
    protected Session $session;

    /**
     * List of trusted proxies
     *
     * @var list<string>
     */
    protected array $trustedProxies = [];

    /**
     * Forwarded directives
     *
     * @var array<array<string, string>>
     */
    protected array $forwardedDirectives;

    /**
     * Accepted MIME types with quality values
     *
     * @var array<string, float>
     */
    protected array $mimeTypes;

    /**
     * Accepted charsets with quality values
     *
     * @var array<string, float>
     */
    protected array $charsets;

    /**
     * Accepted encodings with quality values
     *
     * @var array<string, float>
     */
    protected array $encodings;

    /**
     * Accepted languages with quality values
     *
     * @var array<string, float>
     */
    protected array $languages;

    /**
     * @param array<string, string>                             $input
     * @param array<string, string>                             $query
     * @param array<string, string>                             $cookies
     * @param array<string, array<string, list<string>|string>> $files
     * @param array<string, string>                             $server
     */
    public function __construct(array $input, array $query, array $cookies, array $files, array $server)
    {
        $this->initialize($input, $query, $cookies, $files, $server);
    }

    /**
     * Get request method
     */
    public function method(): RequestMethod
    {
        return RequestMethod::from($this->server->get('REQUEST_METHOD', 'GET'));
    }

    /**
     * Get request root relative to the current script location
     */
    public function root(): string
    {
        return '/' . ltrim(preg_replace('~[^/]+$~', '', $this->server->get('SCRIPT_NAME', '')), '/');
    }

    /**
     * Get request base URI
     */
    public function baseUri(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';

        $host = strtolower((string) $this->server->get('SERVER_NAME'));

        $port = (int) $this->server->get('SERVER_PORT', 80);

        $defaultPort = self::DEFAULT_PORTS[$scheme];

        return $port !== $defaultPort
            ? sprintf('%s://%s:%d/%s/', $scheme, $host, $port, trim($this->root(), '/'))
            : sprintf('%s://%s/%s/', $scheme, $host, trim($this->root(), '/'));
    }

    /**
     * Get the request URI
     */
    public function uri(): string
    {
        $uri = rawurldecode((string) $this->server->get('REQUEST_URI'));
        $root = $this->root();
        if (Str::startsWith($uri, $root)) {
            return Path::join(['/', Str::removeStart($uri, $root)]);
        }
        return $uri;
    }

    /**
     * Get the request absolute URI
     */
    public function absoluteUri(): string
    {
        return $this->baseUri() . ltrim($this->uri(), '/');
    }

    /**
     * Get request IP address
     */
    public function ip(): ?string
    {
        $ip = $this->server->get('REMOTE_ADDR');

        if ($this->isFromTrustedProxy()) {
            return $this->getForwardedDirective('for')[0] ?? $ip;
        }

        return $ip;
    }

    /**
     * Get the request host name
     *
     * @throws UnexpectedValueException If the host is invalid
     */
    public function host(): ?string
    {
        $host = $this->headers->get('Host')
            ?? $this->server->get('SERVER_NAME')
            ?? $this->server->get('SERVER_ADDR');

        if ($this->isFromTrustedProxy()) {
            $host = $this->getForwardedDirective('host')[0] ?? $host;
        }

        if ($host === null) {
            return null;
        }

        // Normalize host by converting to lowercase and trimming whitespace
        $host = strtolower(trim($host));

        if (filter_var(
            $ipv6 = (string) preg_replace('/^\[|\](:\d+)?$/', '', $host),
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV6
        ) !== false) {
            return "[$ipv6]";
        }

        // Remove port number
        $host = (string) preg_replace('/:\d+$/', '', $host);

        if (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false) {
            return $host;
        }

        throw new UnexpectedValueException(sprintf('Invalid host: "%s"', $host));
    }

    /**
     * Get the request port
     */
    public function port(): ?int
    {
        $port = (int) $this->server->get('SERVER_PORT', 80);

        if ($this->isFromTrustedProxy()) {
            return (int) ($this->getForwardedDirective('port')[0] ?? $port);
        }

        return $port;
    }

    /**
     * Get the request content length
     */
    public function contentLength(): ?int
    {
        return $this->server->has('CONTENT_LENGTH')
            ? (int) $this->server->get('CONTENT_LENGTH')
            : null;
    }

    /**
     * Get the request referer
     */
    public function referer(): ?string
    {
        return $this->headers->get('Referer');
    }

    /**
     * Validate the request referer, optionally checking a specific path
     */
    public function validateReferer(string $path = '/'): bool
    {
        $base = Uri::normalize(Uri::base($this->absoluteUri()) . Str::wrap($path, '/'));
        return Str::startsWith((string) $this->referer(), $base);
    }

    /**
     * Get the request server protocol
     */
    public function protocol(): ?string
    {
        return $this->server->get('SERVER_PROTOCOL');
    }

    /**
     * Get the request user agent
     */
    public function userAgent(): ?string
    {
        return $this->headers->get('User-Agent');
    }

    /**
     * Get accepted MIME types with quality values
     *
     * @return array<float>
     */
    public function mimeTypes(): array
    {
        return $this->mimeTypes ??= Header::parseQualityValues($this->headers->get('Accept', '*/*'));
    }

    /**
     * Get accepted encodings with quality values
     *
     * @return array<float>
     */
    public function encodings(): array
    {
        return $this->encodings ??= Header::parseQualityValues($this->headers->get('Accept-Encoding', '*'));
    }

    /**
     * Get accepted languages with quality values
     *
     * @return array<float>
     */
    public function languages(): array
    {
        return $this->languages ??= Header::parseQualityValues($this->headers->get('Accept-Language', '*'));
    }

    /**
     * Get request raw GET or POST data
     */
    public function content(): ?string
    {
        if ($this->method() === RequestMethod::GET) {
            return $this->server->get('QUERY_STRING');
        }
        return @file_get_contents('php://input') ?: null;
    }

    /**
     * Return whether request is secure or not
     */
    public function isSecure(): bool
    {
        $https = $this->server->has('HTTPS') && strtolower((string) $this->server->get('HTTPS')) !== 'off';

        if ($this->isFromTrustedProxy() && ($proto = $this->getForwardedDirective('proto')) !== []) {
            return in_array(strtolower($proto[0]), ['https', 'on', 'ssl', '1'], true);
        }

        return $https;
    }

    /**
     * Return whether a request comes from localhost
     */
    public function isLocalhost(): bool
    {
        return in_array($this->ip(), self::LOCALHOST_IP_ADDRESSES, true);
    }

    /**
     * Return whether a request is an XMLHttpRequest
     */
    public function isXmlHttpRequest(): bool
    {
        return strtolower((string) $this->headers->get('X-Requested-With')) === 'xmlhttprequest';
    }

    /**
     * Get the request type
     */
    public function type(): RequestType
    {
        return $this->isXmlHttpRequest() ? RequestType::XmlHttpRequest : RequestType::Http;
    }

    /**
     * Set trusted proxies
     *
     * @param list<string> $proxies
     */
    public function setTrustedProxies(array $proxies): void
    {
        $this->trustedProxies = $proxies;
    }

    /**
     * Return whether a request comes from a trusted proxy
     */
    public function isFromTrustedProxy(): bool
    {
        return in_array($this->server->get('REMOTE_ADDR', ''), $this->trustedProxies, true);
    }

    /**
     * Create a new Request instance from PHP globals
     */
    public static function fromGlobals(): Request
    {
        return new self(
            $_POST,
            $_GET,
            $_COOKIE,
            $_FILES,
            $_SERVER,
        );
    }

    /**
     * Get the request input data. Corresponds to `$_POST`
     */
    public function input(): RequestData
    {
        return $this->input;
    }

    /**
     * Get the request query data. Corresponds to `$_GET`
     */
    public function query(): RequestData
    {
        return $this->query;
    }

    /**
     * Get the request cookies data. Corresponds to `$_COOKIE`
     */
    public function cookies(): RequestData
    {
        return $this->cookies;
    }

    /**
     * Get the request files data. Corresponds to a normalized version of `$_FILES`
     */
    public function files(): FilesData
    {
        return $this->files;
    }

    /**
     * Get the request server data. Corresponds to `$_SERVER`
     */
    public function server(): ServerData
    {
        return $this->server;
    }

    /**
     * Get the request headers data
     */
    public function headers(): HeadersData
    {
        return $this->headers;
    }

    /**
     * Get the session associated to the request or create a new one
     */
    public function session(): Session
    {
        return $this->session ??= new Session($this);
    }

    /**
     * Get the request session ID
     */
    public function hasPreviousSession(): bool
    {
        $sessionName = $this->session()->name();
        return $this->cookies->has($sessionName) && $this->session()->exists($this->cookies->get($sessionName));
    }

    /**
     * Initialize request data
     *
     * @param array<string, string> $input
     * @param array<string, string> $query
     * @param array<string, string> $cookies
     * @param array<mixed>          $files
     * @param array<string, string> $server
     */
    protected function initialize(array $input, array $query, array $cookies, array $files, array $server): void
    {
        $this->input = new RequestData($this->prepareKeys($input));
        $this->query = new RequestData($this->prepareKeys($query));
        $this->files = new FilesData($this->prepareFiles($files));
        $this->cookies = new RequestData($cookies);
        $this->server = new ServerData($server);
        $this->headers = new HeadersData($this->server->getHeaders());
    }

    /**
     * Get forwarded directives
     *
     * @return array<array<string, string>>
     */
    protected function getForwardedDirectives(): array
    {
        if (isset($this->forwardedDirectives)) {
            return $this->forwardedDirectives;
        }

        $directives = [];

        if (($forwardedHeader = $this->headers->get('Forwarded')) !== null) {
            $directives = array_map(Header::combine(...), Header::split(strtolower((string) $forwardedHeader), ',;='));
        } else {
            foreach (self::FORWARDED_DIRECTIVES as $name) {
                if (($xForwarededHeader = $this->headers->get('X-Forwarded-' . ucfirst($name))) !== null) {
                    foreach (Header::split($xForwarededHeader, ',') as $i => $value) {
                        $directives[$i][$name] = $value;
                    }
                }
            }
        }

        return $this->forwardedDirectives = $directives;
    }

    /**
     * Get a forwarded directive
     *
     * @throws InvalidArgumentException If the forwarded directive is invalid
     *
     * @return list<string>
     */
    protected function getForwardedDirective(string $name): array
    {
        $name = strtolower($name);

        if (!in_array($name, self::FORWARDED_DIRECTIVES, true)) {
            throw new InvalidArgumentException('Invalid forwarded directive');
        }

        $result = [];

        foreach ($this->getForwardedDirectives() as $proxy) {
            if (isset($proxy[$name])) {
                $result[] = $proxy[$name];
            }
        }

        return $result;
    }

    /**
     * Prepare data keys by decoding URL-encoded characters
     *
     * @since 2.2.0
     *
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    protected function prepareKeys(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $result[is_string($key) ? rawurldecode($key) : $key] = is_array($value) ? $this->prepareKeys($value) : $value;
        }

        return $result;
    }

    /**
     * Normalize files data
     *
     * @param array<mixed> $files
     *
     * @return array<mixed>
     */
    protected function prepareFiles(array $files): array
    {
        $result = [];

        foreach ($files as $fieldName => $data) {
            $fieldName = rawurldecode($fieldName);

            if (is_array($data['name'])) {
                foreach (array_keys($data['name']) as $i) {
                    $props = [
                        'name'      => $data['name'][$i],
                        'full_path' => $data['full_path'][$i] ?? '',
                        'type'      => $data['type'][$i] ?? '',
                        'tmp_name'  => $data['tmp_name'][$i] ?? '',
                        'error'     => $data['error'][$i] ?? '',
                        'size'      => $data['size'][$i] ?? '',
                    ];

                    if (is_array($data['name'][$i])) {
                        $result[$fieldName] = $this->prepareFiles([$i => $props]);
                    } else {
                        /**
                         * @var array<string, list<UploadedFile>> $result
                         */
                        $result[$fieldName][] = new UploadedFile($fieldName, $props);
                    }
                }
            } else {
                $result[$fieldName] = new UploadedFile($fieldName, $data);
            }
        }

        return $result;
    }
}
