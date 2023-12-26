<?php

namespace Formwork\Http;

use Formwork\Http\Utils\Header;
use Formwork\Parsers\Json;

class JsonResponse extends Response
{
    /**
     * @inheritdoc
     */
    public function __construct(string $data, ResponseStatus $status = ResponseStatus::OK, array $headers = [])
    {
        $headers += [
            'Content-Type' => Header::make(['application/json', 'charset' => 'utf-8']),
        ];
        parent::__construct($data, $status, $headers);
    }

    /**
     * Shortcut for success response
     *
     * @param array<mixed> $data
     */
    public static function success(string $message, ResponseStatus $status = ResponseStatus::OK, array $data = []): self
    {
        return new static(Json::encode([
            'status'  => 'success',
            'message' => $message,
            'code'    => $status,
            'data'    => $data,
        ]), $status);
    }

    /**
     * Shortcut for error response
     *
     * @param array<mixed> $data
     */
    public static function error(string $message, ResponseStatus $status = ResponseStatus::BadRequest, array $data = []): self
    {
        return new static(Json::encode([
            'status'  => 'error',
            'message' => $message,
            'code'    => $status,
            'data'    => $data,
        ]), $status);
    }
}
