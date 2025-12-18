<?php

namespace Formwork\Controllers;

use Error;
use ErrorException;
use Formwork\Cms\App;
use Formwork\Http\JsonResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Utils\Str;
use Throwable;

final class ErrorsController extends AbstractController implements ErrorsControllerInterface
{
    /**
     * ErrorsController@error action
     *
     * @param array<string, mixed> $data
     */
    public function error(ResponseStatus $responseStatus = ResponseStatus::InternalServerError, ?Throwable $throwable = null, array $data = []): Response
    {
        Response::cleanOutputBuffers();

        if ($this->config->get('system.debug.enabled') || $this->request->isLocalhost()) {
            $data['throwable'] = $throwable;
            $data['stackTrace'] = $throwable !== null ? $this->getTrace($throwable) : [];
        }

        if ($this->request->isXmlHttpRequest()) {
            $message = $responseStatus->message();
            if (isset($data['throwable'])) {
                $message .= ': ' . $data['throwable']->getMessage();
            }
            $response = JsonResponse::error($message, $responseStatus);
        } else {
            $response = new Response($this->view(
                'errors.error',
                [
                    'status'  => $responseStatus->code(),
                    'message' => $responseStatus->message(),
                    ...$data,
                ]
            ), $responseStatus);
        }

        if ($throwable !== null) {
            $this->logThrowable($throwable);
        }

        return $response;
    }

    /**
     * ErrorsController@notFound action
     */
    public function notFound(): Response
    {
        return $this->error(ResponseStatus::NotFound);
    }

    /**
     * ErrorsController@internalServerError action
     */
    public function internalServerError(Throwable $throwable): Response
    {
        return $this->error(ResponseStatus::InternalServerError, $throwable);
    }

    /**
     * ErrorsController@forbidden action
     */
    public function forbidden(): Response
    {
        return $this->error(ResponseStatus::Forbidden);
    }

    /**
     * Log a throwable to the error log
     */
    private function logThrowable(Throwable $throwable): void
    {
        error_log(sprintf(
            "Uncaught %s: %s in %s:%s\nStack trace:\n%s\n",
            $throwable::class,
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine(),
            $throwable->getTraceAsString()
        ));
    }

    /**
     * Get full trace including the exception origin
     *
     * @return array<array{file?: string, line?: int, function?: string, class?: string, object?: object, type?: string, args?: list<mixed>}>
     */
    private function getTrace(Throwable $throwable): array
    {
        $trace = $throwable->getTrace();

        $file = $throwable->getFile();
        $line = $throwable->getLine();

        if ($throwable instanceof Error || $throwable instanceof ErrorException) {
            if (
                isset($trace[0], $trace[0]['class'], $trace[0]['function'])
                && $trace[0]['class'] === App::class
                && Str::endsWith($trace[0]['function'], '{closure}')
            ) {
                array_shift($trace);
            }

            if (isset($trace[0], $trace[0]['file'], $trace[0]['line']) && $trace[0]['file'] === $file && $trace[0]['line'] === $line) {
                return $trace;
            }
        }

        array_unshift($trace, [
            'file' => $file,
            'line' => $line,
        ]);

        return $trace;
    }
}
