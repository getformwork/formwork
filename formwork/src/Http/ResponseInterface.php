<?php

namespace Formwork\Http;

use Formwork\Data\Contracts\ArraySerializable;

interface ResponseInterface extends ArraySerializable
{
    /**
     * Create a new Response instance
     *
     * @param array<string, string> $headers
     */
    public function __construct(string $content, ResponseStatus $status = ResponseStatus::OK, array $headers = []);

    /**
     * @param array{content: string, status: ResponseStatus, headers: array<string, string>} $properties
     */
    public static function __set_state(array $properties): static;

    /**
     * Return Response content
     */
    public function content(): string;

    /**
     * Return HTTP status
     */
    public function status(): ResponseStatus;

    /**
     * Return HTTP headers
     *
     * @return array<string, string>
     */
    public function headers(): array;

    /**
     * Send HTTP status
     */
    public function sendStatus(): void;

    /**
     * Send HTTP status and headers
     */
    public function sendHeaders(): void;

    /**
     * Send HTTP status, headers and render content
     */
    public function send(): void;
}
