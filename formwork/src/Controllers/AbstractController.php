<?php

namespace Formwork\Controllers;

use Formwork\App;
use Formwork\Config\Config;
use Formwork\Http\Request;
use Formwork\Utils\Str;
use Formwork\View\ViewFactory;

abstract class AbstractController
{
    /**
     * Controller name
     */
    protected string $name;

    public function __construct(
        protected App $app,
        protected Config $config,
        protected ViewFactory $viewFactory,
        protected Request $request,
    ) {
        $this->name = strtolower(Str::beforeLast(Str::afterLast(static::class, '\\'), 'Controller'));
    }

    /**
     * Render a view
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $name, array $data = []): string
    {
        return $this->viewFactory->make($name, $data)->render();
    }
}
