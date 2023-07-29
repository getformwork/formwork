<?php

namespace Formwork\Exceptions;

use Exception;
use Formwork\App;

class TranslatedException extends Exception
{
    /**
     * Language string of the translated message
     */
    protected string $languageString;

    /**
     * Create a new TranslatedException instance
     *
     * @param string    $message        Exception message
     * @param string    $languageString Language string of the translated message
     * @param int       $code           Exception code
     * @param Exception $previous       Previous Exception
     */
    public function __construct(string $message, string $languageString, int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->languageString = $languageString;
    }

    /**
     * Get language string
     */
    public function getLanguageString(): string
    {
        return $this->languageString;
    }

    /**
     * Get localized message
     */
    public function getTranslatedMessage(): string
    {
        return App::instance()->translations()->getCurrent()->translate($this->languageString);
    }
}
