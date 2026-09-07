<?php

namespace Formwork\Http;

use Formwork\Http\Header as HttpHeader;
use Formwork\Http\Utils\Header;
use Formwork\Utils\Str;

class Response implements ResponseInterface
{
    /**
     * Response HTTP headers
     */
    protected ResponseHeaders $headers;

    /**
     * @param string         $content        Response content
     * @param ResponseStatus $responseStatus Response HTTP status
     */
    public function __construct(
        protected string $content,
        protected ResponseStatus $responseStatus = ResponseStatus::OK,
        array $headers = [],
    ) {
        $headers += [
            'Content-Type' => Header::make(['text/html', 'charset' => 'utf-8']),
        ];
        $this->headers = new ResponseHeaders($headers);
    }

    public static function __set_state(array $properties): static
    {
        return new static($properties['content'], $properties['status'], $properties['headers']);
    }

    /**
     * Return Response content
     */
    public function content(): string
    {
        return $this->content;
    }

    /**
     * Return HTTP status
     */
    public function status(): ResponseStatus
    {
        return $this->responseStatus;
    }

    /**
     * Return HTTP headers
     */
    public function headers(): ResponseHeaders
    {
        return $this->headers;
    }

    /**
     * Prepare response according to the given HTTP request
     */
    public function prepare(Request $request): static
    {
        if ($this->headers->has('ETag') && $request->headers()->get('If-None-Match') === $this->headers->get('ETag')) {
            $this->responseStatus = ResponseStatus::NotModified;
        }

        if ($this->headers->has('Last-Modified') && $request->headers()->get('If-Modified-Since') === $this->headers->get('Last-Modified')) {
            $this->responseStatus = ResponseStatus::NotModified;
        }

        if ($request->method() === RequestMethod::HEAD || $this->requiresEmptyContent()) {
            $this->content = '';
        }

        if ($this->requiresEmptyContent()) {
            // Disable default MIME type set by PHP
            ini_set('default_mimetype', '');

            $this->headers->remove('Content-Length');
            $this->headers->remove('Content-Type');
        }

        return $this;
    }

    /**
     * Send HTTP status
     */
    public function sendStatus(): void
    {
        Header::sendStatus($this->responseStatus);
    }

    /**
     * Send HTTP status and headers
     */
    public function sendHeaders(): void
    {
        $this->sendStatus();

        foreach (headers_list() as $header) {
            [$name, $value] = HttpHeader::split($header, ':');
            if (strcasecmp($name, 'Set-Cookie') === 0) {
                continue;
            }
            if (!$this->headers->has($name)) {
                $this->headers->set($name, $value);
            }
            header_remove($name);
        }

        if (!$this->headers->has('Content-Type')) {
            $this->headers->set('Content-Type', Header::make(['text/html', 'charset' => 'utf-8']));
        } elseif (Str::startsWith($contentType = $this->headers->get('Content-Type'), 'text/') && !Str::contains($contentType, 'charset')) {
            $this->headers->set('Content-Type', Header::make([$contentType, 'charset' => 'utf-8']));
        }

        if (!$this->headers->has('Cache-Control')) {
            $this->headers->set('Cache-Control', 'no-cache, private');
        }

        foreach ($this->headers as $fieldName => $fieldValue) {
            Header::send($fieldName, $fieldValue);
        }
    }

    /**
     * Send HTTP status, headers and render content
     */
    public function send(): void
    {
        $this->sendHeaders();
        echo $this->content;
        $this->flush();
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'status'  => $this->responseStatus,
            'headers' => $this->headers->toArray(),
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static($data['content'], $data['status'], $data['headers']);
    }

    /**
     * Clean all output buffers which were not sent
     */
    public static function cleanOutputBuffers(): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
    }

    /**
     * Return whether response requires empty content
     */
    protected function requiresEmptyContent(): bool
    {
        return in_array($this->responseStatus, [ResponseStatus::NoContent, ResponseStatus::NotModified], true);
    }

    /**
     * Flush response content
     */
    protected function flush(): void
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            flush();
        }
    }
}
