<?php

namespace Formwork\Core;

use Formwork\Utils\Header;
use ErrorException;

class Errors
{
    public static function setHandlers()
    {
        ini_set('display_errors', 0);
        set_exception_handler(array(static::class, 'exceptionHandler'));
        set_error_handler(array(static::class, 'errorHandler'));
    }

    public static function displayErrorPage($status = 500)
    {
        ob_end_clean();
        Header::status($status);
        $message = Header::$statuses[$status];
        require FORMWORK_PATH . 'error.php';
    }

    public static function exceptionHandler($exception)
    {
        static::displayErrorPage();
        // Throws exception again to be logged
        throw $exception;
    }

    public static function errorHandler($severity, $message, $file, $line)
    {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
}
