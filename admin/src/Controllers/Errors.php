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
        $this->displayError(404, 'not-found', array(
            'href' => $this->uri('/dashboard/'),
            'label' => $this->label('errors.action.return-to-dashboard')
        ));
    }

    /**
     * Errors@internalServerError action
     */
    public function internalServerError()
    {
        $this->displayError(500, 'internal-server-error', array(
            'href' => 'https://github.com/giuscris/formwork/issues',
            'label' => $this->label('errors.action.report-to-github')
        ));
    }

    /**
     * Errors@forbidden action
     */
    public function forbidden()
    {
        $this->displayError(403, 'forbidden', array(
            'href' => $this->uri('/dashboard/'),
            'label' => $this->label('errors.action.return-to-dashboard')
        ));
    }

    /**
     * Display error view with error description
     *
     * @param int|string $status HTTP error status
     * @param string     $name   Error name
     * @param array      $action Action link data
     */
    protected function displayError($status, $name, $action)
    {
        HTTPResponse::cleanOutputBuffers();
        Header::status($status);
        $this->view('errors.error', array(
            'title' => $this->label('errors.error.' . $name . '.status'),
            'code' => $status,
            'status' => $this->label('errors.error.' . $name . '.status'),
            'heading' => $this->label('errors.error.' . $name . '.heading'),
            'description' => $this->label('errors.error.' . $name . '.description'),
            'action' => $action
        ));
        // Don't exit, otherwise the error will not be logged
    }
}
