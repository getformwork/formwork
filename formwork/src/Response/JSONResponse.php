<?php

namespace Formwork\Response;

use Formwork\Parsers\JSON;
use Formwork\Utils\Header;

class JSONResponse extends Response
{
    /**
     * @inheritdoc
     */
    public function __construct(array $data, int $status = 200, array $headers = [])
    {
        $headers += [
            'Content-Type' => Header::make(['application/json', 'charset' => 'utf-8'])
        ];
        parent::__construct(JSON::encode($data), $status, $headers);
    }

    /**
     * Shortcut for success response
     */
    public static function success(string $message, int $status = 200, array $data = []): self
    {
        return new static([
            'status'  => 'success',
            'message' => $message,
            'code'    => $status,
            'data'    => $data
        ], $status);
    }

    /**
     * Shortcut for error response
     */
    public static function error(string $message, int $status = 400, array $data = []): self
    {
        return new static([
            'status'  => 'error',
            'message' => $message,
            'code'    => $status,
            'data'    => $data
        ], $status);
    }
}
