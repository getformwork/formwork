<?php

namespace Formwork\Core;

use Formwork\Utils\Header;

class Output
{
    /**
     * Output content
     *
     * @var string
     */
    protected $content;

    /**
     * Output HTTP status
     *
     * @var int|null
     */
    protected $status;

    /**
     * Output HTTP headers
     *
     * @var array
     */
    protected $headers;

    /**
     * Create a new Output instance
     *
     * @param string   $content
     * @param int|null $status
     * @param array    $headers
     */
    public function __construct($content, $status, array $headers = array())
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
    }

    /**
     * Return output content
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
        if (!is_null($this->status)) {
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
}
