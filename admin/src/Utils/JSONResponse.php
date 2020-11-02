<?php

namespace Formwork\Admin\Utils;

use Formwork\Utils\Header;

class JSONResponse
{
    /**
     * Response HTTP status code
     *
     * @var int|string
     */
    protected $status;

    /**
     * Response data
     *
     * @var array
     */
    protected $data = [];

    /**
     * Create a new JSONResponse instance
     */
    public function __construct(array $data, int $status = 200)
    {
        $this->status = $status;
        $this->data = $data;
    }

    /**
     * Send the JSON response with proper Content-Type
     */
    public function send(): string
    {
        Header::contentType('application/json; charset=utf-8');
        if ($this->status != 200) {
            Header::status($this->status);
        }
        echo json_encode($this->data);
        exit;
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
