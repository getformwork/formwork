<?php

namespace Formwork\Tests\Unit\Controllers\Fixtures;

use Formwork\Controllers\AbstractController;
use Formwork\Fields\FieldCollection;
use Formwork\Forms\Form;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;

/**
 * Exposes the protected helpers of the abstract controller
 */
final class ExposedController extends AbstractController
{
    public function name(): string
    {
        return $this->name;
    }

    public function render(string $name, array $data = []): string
    {
        return $this->view($name, $data);
    }

    public function makeForm(string $name, FieldCollection $fields): Form
    {
        return $this->form($name, $fields);
    }

    public function redirectTo(string $route, ResponseStatus $status = ResponseStatus::Found, array $headers = []): RedirectResponse
    {
        return $this->redirect($route, $status, $headers);
    }

    public function redirectBack(ResponseStatus $status = ResponseStatus::Found, array $headers = [], string $default = '/', string $base = '/'): RedirectResponse
    {
        return $this->redirectToReferer($status, $headers, $default, $base);
    }

    public function forwardTo(string $controller, string $action, array $parameters = []): Response
    {
        return $this->forward($controller, $action, $parameters);
    }

    public function route(string $name, array $params = []): string
    {
        return $this->generateRoute($name, $params);
    }
}
