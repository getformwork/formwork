<?php

namespace Formwork\Images;

use Formwork\Config;

class ImageFactory
{
    public function __construct(protected Config $config)
    {
    }

    public function make(string $path, array $options = [])
    {
        return new Image($path, [...$this->config->get('system.images', []), ...$options]);
    }
}
