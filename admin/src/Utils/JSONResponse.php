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
    protected $data;

    /**
     * Create a new JSONResponse instance
     *
     * @param array      $data
     * @param int|string $status
     */
    public function __construct($data, $status = 200)
    {
        $this->status = $status;
        $this->data = $data;
    }

    /**
     * Send the JSON response with proper Content-Type
     */
    public function send()
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
     *
     * @param string $message
     * @param int    $status
     * @param array  $data
     *
     * @return self
     */
    public static function success($message, $status = 200, $data = array())
    {
        return new static(array(
            'status'  => 'success',
            'message' => $message,
            'code'    => $status,
            'data'    => $data
        ), $status);
    }

    /**
     * Shortcut for error response
     *
     * @param string $message
     * @param int    $status
     * @param array  $data
     *
     * @return self
     */
    public static function error($message, $status = 400, $data = array())
    {
        return new static(array(
            'status'  => 'error',
            'message' => $message,
            'code'    => $status,
            'data'    => $data
        ), $status);
    }
}
