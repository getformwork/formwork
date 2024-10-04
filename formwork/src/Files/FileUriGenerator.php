<?php

namespace Formwork\Files;

use Formwork\Config\Config;
use Formwork\Files\Exceptions\FileUriGenerationException;
use Formwork\Pages\Site;
use Formwork\Router\Router;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use RuntimeException;

class FileUriGenerator
{
    public function __construct(protected Config $config, protected Router $router, protected Site $site)
    {
    }

    public function generate(File $file): string
    {
        $path = $file->path();

        if (Str::startsWith($path, FileSystem::normalizePath($this->config->get('system.images.processPath')))) {
            $id = basename(dirname($path));
            $name = basename($path);
            $uriPath = $this->router->generate('assets', compact('id', 'name'));
            return $this->site->uri($uriPath, includeLanguage: false);
        }

        if (Str::startsWith($path, $contentPath = FileSystem::normalizePath($this->config->get('system.pages.path')))) {
            $uriPath = preg_replace('~[/\\\](\d+-)~', '/', Str::after($path, $contentPath))
                ?? throw new RuntimeException(sprintf('Replacement failed with error: %s', preg_last_error_msg()));
            return $this->site->uri($uriPath, includeLanguage: false);
        }

        throw new FileUriGenerationException(sprintf('Cannot generate uri for "%s": missing file generator', $file->name()));
    }
}
