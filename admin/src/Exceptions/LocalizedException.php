<?php

namespace Formwork\Admin\Exceptions;

use Formwork\Admin\Admin;
use Exception;

class LocalizedException extends Exception
{
    /**
     * Language string of the localized message
     *
     * @var string
     */
    protected $languageString;

    /**
     * Create a new LocalizedException instance
     *
     * @param string    $message        Exception message
     * @param string    $languageString Language string of the localized message
     * @param int       $code           Exception code
     * @param Exception $previous       Previous Exception
     */
    public function __construct($message, $languageString, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->languageString = $languageString;
    }

    /**
     * Get language string of the localized message
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
    public function getLocalizedMessage()
    {
        return Admin::instance()->label($this->languageString);
    }
}
