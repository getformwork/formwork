<?php

namespace Formwork\Images;

use Formwork\Config;

class ImageFactory
{
    public function __construct(protected Config $config)
    {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function make(string $path, array $options = []): Image
    {
        /**
         * @var array<string, mixed>
         */
        $defaults = $this->config->get('system.images', []);

        return new Image($path, [...$defaults, ...$options]);
    }
}
