<?php

namespace Formwork\Exceptions;

use Exception;
use Throwable;

class TranslatedException extends Exception
{
    /**
     * @param string     $message        Exception message
     * @param string     $languageString Language string of the translated message
     * @param int        $code           Exception code
     * @param ?Throwable $previous       Previous throwable
     */
    public function __construct(
        string $message,
        protected string $languageString,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get language string
     */
    public function getLanguageString(): string
    {
        return $this->languageString;
    }
}
