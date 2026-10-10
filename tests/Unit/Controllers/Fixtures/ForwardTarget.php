<?php

namespace Formwork\Tests\Unit\Controllers\Fixtures;

use Formwork\Controllers\AbstractController;
use Formwork\Http\Response;

final class ForwardTarget extends AbstractController
{
    public function greet(string $name = 'nobody'): Response
    {
        return new Response('Hello ' . $name . ' from ' . $this->name);
    }
}
