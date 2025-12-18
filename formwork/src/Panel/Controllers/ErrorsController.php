<?php

namespace Formwork\Panel\Controllers;

use Error;
use ErrorException;
use Formwork\Cms\App;
use Formwork\Controllers\ErrorsControllerInterface;
use Formwork\Http\JsonResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Utils\Str;
use Throwable;

final class ErrorsController extends AbstractController implements ErrorsControllerInterface
{
    /**
     * ErrorsController@error action
     */
    public function error(ResponseStatus $responseStatus = ResponseStatus::InternalServerError, ?Throwable $throwable = null): Response
    {
        return $this->makeErrorResponse($responseStatus, 'internalServerError', [
            'href'  => $this->makeGitHubIssueUri($throwable),
            'label' => $this->translate('panel.errors.action.reportToGithub'),
        ], $throwable);
    }

    /**
     * ErrorsController@notFound action
     */
    public function notFound(): Response
    {
        return $this->makeErrorResponse(ResponseStatus::NotFound, 'notFound', [
            'href'  => $this->panel->uri('/dashboard/'),
            'label' => $this->translate('panel.errors.action.returnToDashboard'),
        ]);
    }

    /**
     * ErrorsController@internalServerError action
     */
    public function internalServerError(Throwable $throwable): Response
    {
        return $this->makeErrorResponse(ResponseStatus::InternalServerError, 'internalServerError', [
            'href'  => $this->makeGitHubIssueUri($throwable),
            'label' => $this->translate('panel.errors.action.reportToGithub'),
        ], $throwable);
    }

    /**
     * ErrorsController@forbidden action
     */
    public function forbidden(): Response
    {
        return $this->makeErrorResponse(ResponseStatus::Forbidden, 'forbidden', [
            'href'  => $this->panel->uri('/dashboard/'),
            'label' => $this->translate('panel.errors.action.returnToDashboard'),
        ]);
    }

    /**
     * Make error response with error description
     *
     * @param array<mixed>         $action
     * @param array<string, mixed> $data
     */
    private function makeErrorResponse(ResponseStatus $responseStatus, string $name, array $action, ?Throwable $throwable = null, array $data = []): Response
    {
        Response::cleanOutputBuffers();

        if ($this->config->get('system.debug.enabled') || $this->request->isLocalhost()) {
            $data['throwable'] = $throwable;
            $data['stackTrace'] = $throwable !== null ? $this->getTrace($throwable) : [];
        }

        if ($this->request->isXmlHttpRequest()) {
            $message = $this->translate('panel.errors.error.' . $name . '.status');
            if (isset($data['throwable'])) {
                $message .= ': ' . $data['throwable']->getMessage();
            }
            $response = JsonResponse::error($message, $responseStatus);
        } else {
            $response = new Response($this->view('errors.error', [
                'title'       => $this->translate('panel.errors.error.' . $name . '.status'),
                'code'        => $responseStatus->code(),
                'status'      => $this->translate('panel.errors.error.' . $name . '.status'),
                'heading'     => $this->translate('panel.errors.error.' . $name . '.heading'),
                'description' => $this->translate('panel.errors.error.' . $name . '.description'),
                'action'      => $action,
                ...$data,
            ]), $responseStatus);
        }

        if ($throwable !== null) {
            $this->logThrowable($throwable);
        }

        return $response;
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

    /**
     * Make a URI to a new GitHub issue with pre-filled data from an (uncaught) exception
     */
    private function makeGitHubIssueUri(?Throwable $throwable): string
    {
        if ($throwable === null) {
            return 'https://github.com/getformwork/formwork/issues/';
        }

        $query = http_build_query([
            'labels' => 'bug',
            'title'  => $throwable->getMessage(),
            'body'   => sprintf(
                "### Description\n\n[Please enter a description and the steps to reproduce the problem...]\n\n"
                    . "**Formwork**: %s\n**Php**: %s\n**OS**: %s\n**SAPI**: %s\n\n"
                    . "### Stack Trace\n```\nUncaught %s: %s in %s:%s\n\n%s\n",
                $this->app::VERSION,
                PHP_VERSION,
                PHP_OS_FAMILY,
                PHP_SAPI,
                $throwable::class,
                $throwable->getMessage(),
                $throwable->getFile(),
                $throwable->getLine(),
                $throwable->getTraceAsString()
            ),
        ]);

        return 'https://github.com/getformwork/formwork/issues/new/?' . $query;
    }
}
