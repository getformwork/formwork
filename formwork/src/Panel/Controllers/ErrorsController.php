<?php

namespace Formwork\Panel\Controllers;

use Formwork\Formwork;
use Formwork\Response\Response;
use Formwork\Utils\HTTPResponse;
use Throwable;

class ErrorsController extends AbstractController
{
    /**
     * Errors@notFound action
     */
    public function notFound(): Response
    {
        return $this->makeErrorResponse(404, 'notFound', [
            'href'  => $this->panel()->uri('/dashboard/'),
            'label' => $this->translate('panel.errors.action.returnToDashboard'),
        ]);
    }

    /**
     * Errors@internalServerError action
     */
    public function internalServerError(Throwable $exception): Response
    {
        return $this->makeErrorResponse(500, 'internalServerError', [
            'href'  => $this->makeGitHubIssueUri($exception),
            'label' => $this->translate('panel.errors.action.reportToGithub'),
        ]);
    }

    /**
     * Errors@forbidden action
     */
    public function forbidden(): Response
    {
        return $this->makeErrorResponse(403, 'forbidden', [
            'href'  => $this->panel()->uri('/dashboard/'),
            'label' => $this->translate('panel.errors.action.returnToDashboard'),
        ]);
    }

    /**
     * Make error response with error description
     *
     * @param int    $status HTTP error status
     * @param string $name   Error name
     * @param array  $action Action link data
     */
    protected function makeErrorResponse(int $status, string $name, array $action): Response
    {
        HTTPResponse::cleanOutputBuffers();
        return new Response($this->view('errors.error', [
            'title'       => $this->translate('panel.errors.error.' . $name . '.status'),
            'code'        => $status,
            'status'      => $this->translate('panel.errors.error.' . $name . '.status'),
            'heading'     => $this->translate('panel.errors.error.' . $name . '.heading'),
            'description' => $this->translate('panel.errors.error.' . $name . '.description'),
            'action'      => $action,
        ], true), $status);
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
                "**Formwork**: %s\n**PHP**: %s\n**OS**: %s\n**SAPI**: %s\n\n" .
                "### Stack Trace\n```\nUncaught %s: %s in %s:%s\n\n%s\n",
                Formwork::VERSION,
                PHP_VERSION,
                PHP_OS_FAMILY,
                PHP_SAPI,
                get_class($exception),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString()
            ),
        ]);

        return 'https://github.com/getformwork/formwork/issues/new/?' . $query;
    }
}
