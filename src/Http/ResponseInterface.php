<?php

namespace Formwork\Http;

use Formwork\Data\Contracts\ArraySerializable;

interface ResponseInterface extends ArraySerializable
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(string $content, ResponseStatus $responseStatus = ResponseStatus::OK, array $headers = []);

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
     */
    public function headers(): ResponseHeaders;

    /**
     * Prepare response according to the given HTTP request
     */
    public function prepare(Request $request): static;

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
