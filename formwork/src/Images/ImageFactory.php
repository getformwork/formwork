<?php

namespace Formwork\Images;

use Formwork\Config\Config;

final class ImageFactory
{
    public function __construct(
        private Config $config,
    ) {}

    /**
     * Create a new Image instance
     *
     * @param array<string, mixed> $options
     */
    public function make(string $path, array $options = []): Image
    {
        /**
         * @var array<string, mixed>
         */
        $defaults = $this->config->getArray('system.images', []);

        return new Image($path, [...$defaults, ...$options]);
    }
}
