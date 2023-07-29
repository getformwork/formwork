<?php

namespace Formwork\Files;

use Exception;
use Formwork\Config;
use Formwork\Pages\Site;
use Formwork\Router\Router;
use Formwork\Utils\Str;

class FileUriGenerator
{
    public function __construct(protected Config $config, protected Router $router, protected Site $site)
    {

    }

    public function generate(File $file)
    {
        $path = $file->path();

        if (Str::startsWith($path, $this->config->get('system.images.processPath'))) {
            $id = basename(dirname($path));
            $name = basename($path);
            $uriPath = $this->router->generate('assets', compact('id', 'name'));
            return $this->site->uri($uriPath, includeLanguage: false);
        }

        if (Str::startsWith($path, $contentPath = $this->config->get('system.content.path'))) {
            $uriPath = Str::after($path, $contentPath);
            return $this->site->uri($uriPath, includeLanguage: false);
        }

        throw new Exception('Cannot generate URI');
    }
}
