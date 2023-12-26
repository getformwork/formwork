<?php

namespace Formwork\Panel\Controllers;

use Formwork\Http\JsonResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Throwable;

class ErrorsController extends AbstractController
{
    /**
     * Errors@notFound action
     */
    public function notFound(): Response
    {
        return $this->makeErrorResponse(ResponseStatus::NotFound, 'notFound', [
            'href'  => $this->panel()->uri('/dashboard/'),
            'label' => $this->translate('panel.errors.action.returnToDashboard'),
        ]);
    }

    /**
     * Errors@internalServerError action
     */
    public function internalServerError(Throwable $exception): Response
    {
        return $this->makeErrorResponse(ResponseStatus::InternalServerError, 'internalServerError', [
            'href'  => $this->makeGitHubIssueUri($exception),
            'label' => $this->translate('panel.errors.action.reportToGithub'),
        ]);
    }

    /**
     * Errors@forbidden action
     */
    public function forbidden(): Response
    {
        return $this->makeErrorResponse(ResponseStatus::Forbidden, 'forbidden', [
            'href'  => $this->panel()->uri('/dashboard/'),
            'label' => $this->translate('panel.errors.action.returnToDashboard'),
        ]);
    }

    /**
     * Make error response with error description
     *
     * @param array<mixed> $action
     */
    protected function makeErrorResponse(ResponseStatus $status, string $name, array $action): Response
    {
        Response::cleanOutputBuffers();

        if ($this->request->isXmlHttpRequest()) {
            return JsonResponse::error('Error', $status);
        }

        return new Response($this->view('errors.error', [
            'title'       => $this->translate('panel.errors.error.' . $name . '.status'),
            'code'        => $status->code(),
            'status'      => $this->translate('panel.errors.error.' . $name . '.status'),
            'heading'     => $this->translate('panel.errors.error.' . $name . '.heading'),
            'description' => $this->translate('panel.errors.error.' . $name . '.description'),
            'action'      => $action,
        ]), $status);
    }

    /**
     * Make a URI to a new GitHub issue with pre-filled data from an (uncaught) exception
     */
    protected function makeGitHubIssueUri(Throwable $exception): string
    {
        $query = http_build_query([
            'labels' => 'bug',
            'title'  => $exception->getMessage(),
            'body'   => sprintf(
                "### Description\n\n[Please enter a description and the steps to reproduce the problem...]\n\n" .
                "**Formwork**: %s\n**Php**: %s\n**OS**: %s\n**SAPI**: %s\n\n" .
                "### Stack Trace\n```\nUncaught %s: %s in %s:%s\n\n%s\n",
                $this->app::VERSION,
                PHP_VERSION,
                PHP_OS_FAMILY,
                PHP_SAPI,
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString()
            ),
        ]);

        return 'https://github.com/getformwork/formwork/issues/new/?' . $query;
    }
}
