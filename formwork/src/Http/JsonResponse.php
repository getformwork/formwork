<?php

namespace Formwork\Http;

use Formwork\Http\Utils\Header;
use Formwork\Parsers\Json;

class JsonResponse extends Response
{
    /**
     * @inheritdoc
     */
    public function __construct(array $data, ResponseStatus $status = ResponseStatus::OK, array $headers = [])
    {
        $headers += [
            'Content-Type' => Header::make(['application/json', 'charset' => 'utf-8']),
        ];
        parent::__construct(Json::encode($data), $status, $headers);
    }

    /**
     * Shortcut for success response
     */
    public static function success(string $message, ResponseStatus $status = ResponseStatus::OK, array $data = []): self
    {
        return new static([
            'status'  => 'success',
            'message' => $message,
            'code'    => $status,
            'data'    => $data,
        ], $status);
    }

    /**
     * Shortcut for error response
     */
    public static function error(string $message, ResponseStatus $status = ResponseStatus::BadRequest, array $data = []): self
    {
        return new static([
            'status'  => 'error',
            'message' => $message,
            'code'    => $status,
            'data'    => $data,
        ], $status);
    }
}
