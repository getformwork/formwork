<?php

namespace Formwork\Admin\Exceptions;

use Formwork\Admin\Admin;
use Exception;

class TranslatedException extends Exception
{
    /**
     * Language string of the translated message
     *
     * @var string
     */
    protected $languageString;

    /**
     * Create a new TranslatedException instance
     *
     * @param string    $message        Exception message
     * @param string    $languageString Language string of the translated message
     * @param int       $code           Exception code
     * @param Exception $previous       Previous Exception
     */
    public function __construct(string $message, string $languageString, int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->languageString = $languageString;
    }

    /**
     * Get language string
     *
     * @return string
     */
    public function getLanguageString()
    {
        return $this->languageString;
    }

    /**
     * Get localized message
     *
     * @return string
     */
    public function getTranslatedMessage()
    {
        return Admin::instance()->label($this->languageString);
    }
}
