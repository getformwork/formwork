<?php

namespace Formwork\Admin\Controllers;

use Formwork\Formwork;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPResponse;
use Throwable;

class Errors extends AbstractController
{
    /**
     * Errors@notFound action
     */
    public function notFound(): void
    {
        $this->displayError(404, 'not-found', [
            'href'  => $this->admin()->uri('/dashboard/'),
            'label' => $this->admin()->translate('admin.errors.action.return-to-dashboard')
        ]);
    }

    /**
     * Errors@internalServerError action
     */
    public function internalServerError(Throwable $exception): void
    {
        $this->displayError(500, 'internal-server-error', [
            'href'  => $this->makeGitHubIssueUri($exception),
            'label' => $this->admin()->translate('admin.errors.action.report-to-github')
        ]);
    }

    /**
     * Errors@forbidden action
     */
    public function forbidden(): void
    {
        $this->displayError(403, 'forbidden', [
            'href'  => $this->admin()->uri('/dashboard/'),
            'label' => $this->admin()->translate('admin.errors.action.return-to-dashboard')
        ]);
    }

    /**
     * Display error view with error description
     *
     * @param int    $status HTTP error status
     * @param string $name   Error name
     * @param array  $action Action link data
     */
    protected function displayError(int $status, string $name, array $action): void
    {
        HTTPResponse::cleanOutputBuffers();
        Header::status($status);
        $this->view('errors.error', [
            'title'       => $this->admin()->translate('admin.errors.error.' . $name . '.status'),
            'code'        => $status,
            'status'      => $this->admin()->translate('admin.errors.error.' . $name . '.status'),
            'heading'     => $this->admin()->translate('admin.errors.error.' . $name . '.heading'),
            'description' => $this->admin()->translate('admin.errors.error.' . $name . '.description'),
            'action'      => $action
        ]);
        // Don't exit, otherwise the error will not be logged
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
            )
        ]);

        return 'https://github.com/getformwork/formwork/issues/new/?' . $query;
    }
}
