<?php

namespace Formwork\Core;

use Formwork\Utils\Header;
use Formwork\Utils\HTTPResponse;
use ErrorException;

class Errors
{
    /**
     * Set error handlers
     */
    public static function setHandlers()
    {
        ini_set('display_errors', 0);
        set_exception_handler(array(static::class, 'exceptionHandler'));
        set_error_handler(array(static::class, 'errorHandler'));
    }

    /**
     * Display error page
     *
     * @param int $status HTTP status code
     */
    public static function displayErrorPage($status = 500)
    {
        HTTPResponse::cleanOutputBuffers();
        Header::status($status);
        $message = Header::$statuses[$status];
        require FORMWORK_PATH . 'error.php';
        // Don't exit, otherwise the error will not be logged
    }

    /**
     * Display error page on exception
     *
     * @param ErrorException $exception
     */
    public static function exceptionHandler($exception)
    {
        static::displayErrorPage();
        // Throws exception again to be logged
        throw $exception;
    }

    /**
     * Handle error throwing an ErrorException
     *
     * @param int    $severity
     * @param string $message
     * @param string $file
     * @param string $line
     *
     * @return bool|ErrorException
     */
    public static function errorHandler($severity, $message, $file, $line)
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
}
