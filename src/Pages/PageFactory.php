<?php

namespace Formwork\Pages;

use Formwork\Services\Container;

final class PageFactory
{
    public function __construct(
        private Container $container,
    ) {}

    /**
     * Create a new Page instance
     *
     * @param array<string, mixed> $data
     */
    public function make(array $data = []): Page
    {
        return $this->container->build(Page::class, ['data' => $data]);
    }
}
