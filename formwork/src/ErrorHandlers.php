<?php

namespace Formwork;

use ErrorException;
use Formwork\Http\JsonResponse;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\View\ViewFactory;
use Throwable;

class ErrorHandlers
{
    public function __construct(protected ViewFactory $viewFactory, protected Request $request)
    {

    }

    /**
     * Set error handlers
     */
    public function setHandlers(): void
    {
        ini_set('display_errors', 0);
        set_exception_handler($this->getExceptionHandler(...));
        set_error_handler($this->getErrorHandler(...));
    }

    /**
     * Display error page
     *
     * @param int $status HTTP status code
     */
    public function displayErrorPage(ResponseStatus $status = ResponseStatus::InternalServerError): void
    {
        Response::cleanOutputBuffers();

        if ($this->request->isXmlHttpRequest()) {
            JsonResponse::error('Error', $status)->send();
        } else {
            $view = $this->viewFactory->make('errors.error', ['status' => $status->code(), 'message' => $status->message()]);
            $response = new Response($view->render(true), $status);
            $response->send();
            // Don't exit, otherwise the error will not be logged
        }
    }

    /**
     * Display error page on exception
     */
    public function getExceptionHandler(Throwable $exception): void
    {
        static::displayErrorPage();
        error_log(sprintf(
            "Uncaught %s: %s in %s:%s\nStack trace:\n%s\n",
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        ));
    }

    /**
     * Handle error throwing an ErrorException
     */
    public function getErrorHandler(int $severity, string $message, string $file, int $line)
    {
        if (!(error_reporting() & $severity) || $severity === E_USER_DEPRECATED) {
            return false;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
}
