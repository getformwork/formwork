<?php

namespace Formwork\Core;

use Formwork\Utils\Header;
use Formwork\Utils\HTTPResponse;
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
        HTTPResponse::cleanOutputBuffers();
        Header::status($status);
        $message = Header::$statuses[$status];
        require FORMWORK_PATH . 'error.php';
        exit;
    }

    public static function exceptionHandler($exception)
    {
        static::displayErrorPage();
        // Throws exception again to be logged
        throw $exception;
    }

    public static function errorHandler($severity, $message, $file, $line)
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
}
