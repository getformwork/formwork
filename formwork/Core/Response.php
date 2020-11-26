<?php

namespace Formwork\Core;

use Formwork\Utils\Header;

class Response
{
    /**
     * Response content
     *
     * @var string
     */
    protected $content;

    /**
     * Response HTTP status
     *
     * @var int|null
     */
    protected $status;

    /**
     * Response HTTP headers
     *
     * @var array
     */
    protected $headers;

    /**
     * Create a new Response instance
     */
    public function __construct(string $content, int $status = null, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
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
    public function status(): int
    {
        return $this->status;
    }

    /**
     * Return HTTP headers
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Send HTTP status
     */
    public function sendStatus(): void
    {
        if ($this->status !== null) {
            Header::status($this->status);
        }
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
    public function render(): void
    {
        $this->sendHeaders();
        echo $this->content;
    }
}
