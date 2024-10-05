<?php

namespace Formwork\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;
use Formwork\Templates\TemplateFactory;
use Formwork\Templates\Templates;
use Formwork\Utils\FileSystem;

class TemplatesServiceLoader implements ServiceLoaderInterface
{
    public function __construct(protected Config $config, protected TemplateFactory $templateFactory)
    {
    }

    public function load(Container $container): Templates
    {
        $path = $this->config->get('system.templates.path');

        $templates = [];

        foreach (FileSystem::listFiles($path) as $file) {
            if (FileSystem::extension($file) === 'php') {
                $name = FileSystem::name($file);
                $templates[$name] = $this->templateFactory->make($name);
            }
        }

        return new Templates($templates);
    }
}
