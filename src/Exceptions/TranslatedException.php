<?php

namespace Formwork\Exceptions;

use Exception;

class TranslatedException extends Exception
{
    /**
     * @param string    $message           Exception message
     * @param string    $languageString    Language string of the translated message
     * @param int       $code              Exception code
     * @param Exception $previousException Previous Exception
     */
    public function __construct(
        string $message,
        protected string $languageString,
        int $code = 0,
        ?Exception $previousException = null,
    ) {
        parent::__construct($message, $code, $previousException);
    }

    /**
     * Get language string
     */
    public function getLanguageString(): string
    {
        return $this->languageString;
    }
}
