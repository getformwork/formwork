<?php

namespace Formwork;

use Formwork\Utils\Header;
use Formwork\Utils\HTTPResponse;
use Formwork\View\View;
use ErrorException;
use Formwork\Response\Response;
use Throwable;

class Errors
{
    /**
     * Set error handlers
     */
    public static function setHandlers(): void
    {
        ini_set('display_errors', 0);
        set_exception_handler([static::class, 'exceptionHandler']);
        set_error_handler([static::class, 'errorHandler']);
    }

    /**
     * Display error page
     *
     * @param int $status HTTP status code
     */
    public static function displayErrorPage(int $status = 500): void
    {
        HTTPResponse::cleanOutputBuffers();
        $view = new View('errors.error', ['status' => $status, 'message' => Header::HTTP_STATUS[$status]]);
        $response = new Response($view->render(true), $status);
        $response->send();
        // Don't exit, otherwise the error will not be logged
    }

    /**
     * Display error page on exception
     */
    public static function exceptionHandler(Throwable $exception): void
    {
        static::displayErrorPage();
        error_log(sprintf(
            "Uncaught %s: %s in %s:%s\nStack trace:\n%s\n",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        ));
    }

    /**
     * Handle error throwing an ErrorException
     */
    public static function errorHandler(int $severity, string $message, string $file, int $line)
    {
        if (!(error_reporting() & $severity) || $severity === E_USER_DEPRECATED) {
            return false;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
}
