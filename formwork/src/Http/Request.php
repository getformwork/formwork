<?php

namespace Formwork\Http;

use Exception;
use Formwork\Http\Files\UploadedFile;
use Formwork\Http\Session\Session;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;

class Request
{
    public const DEFAULT_PORTS = ['http' => 80, 'https' => 430];

    protected const LOCALHOST_IP_ADDRESSES = ['127.0.0.1', '::1'];

    protected const FORWARDED_DIRECTIVES = ['for', 'host', 'proto', 'port'];

    protected readonly RequestData $input;

    protected readonly RequestData $query;

    protected readonly RequestData $cookies;

    protected readonly FilesData $files;

    protected readonly ServerData $server;

    protected readonly HeadersData $headers;

    protected readonly Session $session;

    protected array $trustedProxies = [];

    protected readonly array $forwardedDirectives;

    protected readonly array $mimeTypes;

    protected readonly array $charsets;

    protected readonly array $encodings;

    protected readonly array $languages;

    public function __construct(array $input, array $query, array $cookies, array $files, array $server)
    {
        $this->initialize($input, $query, $cookies, $files, $server);
    }

    public function method(): RequestMethod
    {
        return RequestMethod::from($this->server->get('REQUEST_METHOD', 'GET'));
    }

    public function root(): string
    {
        return '/' . ltrim(preg_replace('~[^/]+$~', '', $this->server->get('SCRIPT_NAME', '')), '/');
    }

    public function baseUri(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';

        $host = strtolower($this->server->get('SERVER_NAME'));

        $port = (int) $this->server->get('SERVER_PORT', 80);

        $defaultPort = self::DEFAULT_PORTS[$scheme] ?? null;

        return Path::join(
            [
                $port !== $defaultPort
                    ? sprintf('%s://%s:%d/%s', $scheme, $host, $port)
                    : sprintf('%s://%s', $scheme, $host),
                $this->root(),
            ]
        );
    }

    public function uri(): string
    {
        $uri = urldecode($this->server->get('REQUEST_URI'));
        $root = $this->root();
        if (Str::startsWith($uri, $root)) {
            return Path::join(['/', Str::removeStart($uri, $root)]);
        }
        return $uri;
    }

    public function absoluteUri(): string
    {
        return Path::join(
            [
                $this->baseUri(),
                $this->uri(),
            ]
        );
    }

    public function ip(): ?string
    {
        $ip = $this->server->get('REMOTE_ADDR');

        if ($this->isFromTrustedProxy()) {
            return $this->getForwardedDirective('for')[0] ?? $ip;
        }

        return $ip;
    }

    public function host(): ?string
    {
        $host = $this->headers->get('Host');

        if ($this->isFromTrustedProxy()) {
            return $this->getForwardedDirective('host')[0] ?? $host;
        }

        return $host;
    }

    public function port(): ?int
    {
        $port = (int) $this->server->get('SERVER_PORT', 80);

        if ($this->isFromTrustedProxy()) {
            return (int) $this->getForwardedDirective('port')[0] ?? $port;
        }

        return $port;
    }

    public function contentLength(): ?int
    {
        return $this->server->has('CONTENT_LENGTH')
            ? (int) $this->server->get('CONTENT_LENGTH')
            : null;
    }

    public function referer(): ?string
    {
        return $this->headers->get('Referer');
    }

    public function validateReferer(?string $path = null): bool
    {
        $base = Uri::normalize(Uri::base() . '/' . $path);
        return Str::startsWith((string) $this->referer(), $base);
    }

    public function protocol(): ?string
    {
        return $this->server->get('SERVER_PROTOCOL');
    }

    /**
     * Get request user agent
     */
    public function userAgent(): ?string
    {
        return $this->headers->get('User-Agent');
    }

    public function mimeTypes(): array
    {
        return $this->mimeTypes ??= Header::parseQualityValues($this->headers->get('Accept', '*/*'));
    }

    public function encodings(): array
    {
        return $this->encodings ??= Header::parseQualityValues($this->headers->get('Accept-Encoding', '*'));
    }

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
        return file_get_contents('php://input');
    }

    /**
     * Return whether request is secure or not
     */
    public function isSecure(): bool
    {
        $https = $this->server->has('HTTPS') && strtolower($this->server->get('HTTPS')) !== 'off';

        if ($this->isFromTrustedProxy() && ($proto = $this->getForwardedDirective('proto')) !== []) {
            return in_array(strtolower($proto[0]), ['https', 'on', 'ssl', '1'], true);
        }

        return $https;
    }

    /*
     * Return whether a request comes from localhost
     */
    public function isLocalhost(): bool
    {
        return in_array($this->ip(), self::LOCALHOST_IP_ADDRESSES, true);
    }

    public function isXmlHttpRequest(): bool
    {
        return strtolower($this->headers->get('X-Requested-With', '')) === 'xmlhttprequest';
    }

    public function type(): RequestType
    {
        return $this->isXmlHttpRequest() ? RequestType::XmlHttpRequest : RequestType::Http;
    }

    public function setTrustedProxies(array $proxies): void
    {
        $this->trustedProxies = $proxies;
    }

    public function isFromTrustedProxy(): bool
    {
        return in_array($this->server->get('REMOTE_ADDR', ''), $this->trustedProxies, true);
    }

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

    public function input(): RequestData
    {
        return $this->input;
    }

    public function query(): RequestData
    {
        return $this->query;
    }

    public function cookies(): RequestData
    {
        return $this->cookies;
    }

    public function files(): FilesData
    {
        return $this->files;
    }

    public function server(): ServerData
    {
        return $this->server;
    }

    public function headers(): HeadersData
    {
        return $this->headers;
    }

    public function session(): Session
    {
        return $this->session ??= new Session($this);
    }

    public function hasPreviousSession(): bool
    {
        $sessionName = $this->session()->name();
        return $this->cookies->has($sessionName) && $this->session()->exists($this->cookies->get($sessionName));
    }

    protected function initialize(array $input, array $query, array $cookies, array $files, array $server): void
    {
        $this->input = new RequestData($input);
        $this->query = new RequestData($query);
        $this->files = $this->prepareFiles($files);
        $this->cookies = new RequestData($cookies);
        $this->server = new ServerData($server);
        $this->headers = new HeadersData($this->server->getHeaders());
    }

    protected function getForwardedDirectives(): array
    {
        if (isset($this->forwardedDirectives)) {
            return $this->forwardedDirectives;
        }

        $directives = [];

        if (($forwardedHeader = $this->headers->get('Forwarded')) !== null) {
            $directives = array_map(Header::combine(...), Header::split(strtolower($forwardedHeader), ',;='));
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

    protected function getForwardedDirective(string $name): array
    {
        $name = strtolower($name);

        if (!in_array($name, self::FORWARDED_DIRECTIVES, true)) {
            throw new Exception('Invalid forwarded directive');
        }

        $result = [];

        foreach ($this->getForwardedDirectives() as $proxy) {
            if (isset($proxy[$name])) {
                $result[] = $proxy[$name];
            }
        }

        return $result;
    }

    protected function prepareFiles(array $files): FilesData
    {
        $result = [];

        foreach ($files as $fieldName => $data) {
            if (is_array($data['name'])) {
                foreach ($data['name'] as $i => $name) {
                    $result[$fieldName][] = new UploadedFile($fieldName, [
                        'name'      => $data['name'][$i] ?? '',
                        'full_path' => $data['full_path'][$i] ?? '',
                        'type'      => $data['type'][$i] ?? '',
                        'tmp_name'  => $data['tmp_name'][$i] ?? '',
                        'error'     => $data['error'][$i] ?? '',
                        'size'      => $data['size'][$i] ?? '',
                    ]);
                }
            } else {
                $result[$fieldName] = new UploadedFile($fieldName, $data);
            }
        }

        return new FilesData($result);
    }
}
