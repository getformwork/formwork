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
     *
     * @param string   $content
     * @param int|null $status
     * @param array    $headers
     */
    public function __construct($content, $status = null, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
    }

    /**
     * Return Response content
     *
     * @return string
     */
    public function content()
    {
        return $this->content;
    }

    /**
     * Return HTTP status
     *
     * @return int
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * Return HTTP headers
     *
     * @return array
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * Send HTTP status
     */
    public function sendStatus()
    {
        if ($this->status !== null) {
            Header::status($this->status);
        }
    }

    /**
     * Send HTTP status and headers
     */
    public function sendHeaders()
    {
        $this->sendStatus();

        if (!empty($this->headers)) {
            foreach ($this->headers as $fieldName => $fieldValue) {
                Header::send($fieldName, $fieldValue);
            }
        }
    }

    /**
     * Send HTTP status, headers and render content
     */
    public function render()
    {
        $this->sendHeaders();
        echo $this->content;
    }
}
