<?php

namespace Formwork\Http;

use Formwork\Http\Utils\Header;
use Formwork\Parsers\Json;

class JsonResponse extends Response
{
    public function __construct(string $data, ResponseStatus $responseStatus = ResponseStatus::OK, array $headers = [])
    {
        $headers += [
            'Content-Type' => Header::make(['application/json', 'charset' => 'utf-8']),
        ];
        parent::__construct($data, $responseStatus, $headers);
    }

    /**
     * Shortcut for success response
     *
     * @param array<mixed> $data
     */
    public static function success(string $message, ResponseStatus $responseStatus = ResponseStatus::OK, array $data = []): self
    {
        return new static(Json::encode([
            'status'  => 'success',
            'message' => $message,
            'code'    => $responseStatus,
            'data'    => $data,
        ]), $responseStatus);
    }

    /**
     * Shortcut for error response
     *
     * @param array<mixed> $data
     */
    public static function error(string $message, ResponseStatus $responseStatus = ResponseStatus::BadRequest, array $data = []): self
    {
        return new static(Json::encode([
            'status'  => 'error',
            'message' => $message,
            'code'    => $responseStatus,
            'data'    => $data,
        ]), $responseStatus);
    }
}
