<?php

namespace Formwork\Admin\Exceptions;

use Formwork\Admin\Admin;
use Exception;

class LocalizedException extends Exception
{
    protected $languageString;

    public function __construct($message, $languageString, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->languageString = $languageString;
    }

    public function getLanguageString()
    {
        return $this->languageString;
    }

    public function getLocalizedMessage()
    {
        return Admin::instance()->label($this->languageString);
    }
}
