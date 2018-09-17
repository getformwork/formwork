<?php

namespace Formwork\Admin\Controllers;

use Formwork\Utils\Header;
use Formwork\Utils\HTTPResponse;

class Errors extends AbstractController
{
    public function notFound()
    {
        $this->displayError(404, 'not-found', array(
            'href' => $this->uri('/dashboard/'),
            'label' => $this->label('errors.action.return-to-dashboard')
        ));
    }

    public function internalServerError()
    {
        $this->displayError(500, 'internal-server-error', array(
            'href' => 'https://github.com/giuscris/formwork/issues',
            'label' => $this->label('errors.action.report-to-github')
        ));
    }

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
    }
}
