<?php

namespace Formwork\Admin\Controllers;

use Formwork\Utils\Header;
use Formwork\Utils\HTTPResponse;

class Errors extends AbstractController
{
    /**
     * Errors@notFound action
     */
    public function notFound()
    {
        $this->displayError(404, 'not-found', [
            'href'  => $this->uri('/dashboard/'),
            'label' => $this->label('errors.action.return-to-dashboard')
        ]);
    }

    /**
     * Errors@internalServerError action
     */
    public function internalServerError()
    {
        $this->displayError(500, 'internal-server-error', [
            'href'  => 'https://github.com/getformwork/formwork/issues',
            'label' => $this->label('errors.action.report-to-github')
        ]);
    }

    /**
     * Errors@forbidden action
     */
    public function forbidden()
    {
        $this->displayError(403, 'forbidden', [
            'href'  => $this->uri('/dashboard/'),
            'label' => $this->label('errors.action.return-to-dashboard')
        ]);
    }

    /**
     * Display error view with error description
     *
     * @param int    $status HTTP error status
     * @param string $name   Error name
     * @param array  $action Action link data
     */
    protected function displayError(int $status, string $name, array $action)
    {
        HTTPResponse::cleanOutputBuffers();
        Header::status($status);
        $this->view('errors.error', [
            'title'       => $this->label('errors.error.' . $name . '.status'),
            'code'        => $status,
            'status'      => $this->label('errors.error.' . $name . '.status'),
            'heading'     => $this->label('errors.error.' . $name . '.heading'),
            'description' => $this->label('errors.error.' . $name . '.description'),
            'action'      => $action
        ]);
        // Don't exit, otherwise the error will not be logged
    }
}
