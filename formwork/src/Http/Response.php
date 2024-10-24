<?php

namespace Formwork\Http;

use Formwork\Http\Utils\Header;

class Response implements ResponseInterface
{
    /**
     * Response HTTP headers
     *
     * @var array<string, string>
     */
    protected array $headers;

    /**
     * Create a new Response instance
     *
     * @param string         $content        Response content
     * @param ResponseStatus $responseStatus Response HTTP status
     */
    public function __construct(protected string $content, protected ResponseStatus $responseStatus = ResponseStatus::OK, array $headers = [])
    {
        $headers += [
            'Content-Length' => (string) strlen($content),
            'Content-Type'   => Header::make(['text/html', 'charset' => 'utf-8']),
        ];
        $this->headers = $headers;
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
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Prepare response according to the given HTTP request
     */
    public function prepare(Request $request): static
    {
        if ($request->method() === RequestMethod::HEAD || $this->requiresEmptyContent()) {
            $this->content = '';
        }

        return $this;
    }

    /**
     * Send HTTP status
     */
    public function sendStatus(): void
    {
        Header::status($this->responseStatus);
    }

    /**
     * Send HTTP status and headers
     */
    public function sendHeaders(): void
    {
        $this->sendStatus();

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
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'status'  => $this->responseStatus,
            'headers' => $this->headers,
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

    protected function requiresEmptyContent(): bool
    {
        return in_array($this->responseStatus, [ResponseStatus::NoContent, ResponseStatus::NotModified], true);
    }
}
