<?php

namespace Formwork\Files;

use Formwork\Cms\Site;
use Formwork\Config\Config;
use Formwork\Files\Exceptions\FileUriGenerationException;
use Formwork\Http\Request;
use Formwork\Router\Router;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use RuntimeException;

class FileUriGenerator
{
    public function __construct(
        protected Config $config,
        protected Router $router,
        protected Request $request,
        protected Site $site,
    ) {}

    /**
     * Generate URI for the given File
     */
    public function generate(File $file): string
    {
        $path = $file->path();

        if (Str::startsWith($path, FileSystem::normalizePath($this->config->getString('system.files.paths.site')))) {
            $name = basename($path);
            $uriPath = $this->router->generate('files', compact('name'));
            return $this->site->uri($uriPath, includeLanguage: false);
        }

        if (Str::startsWith($path, FileSystem::normalizePath($this->config->getString('system.images.processPath')))) {
            $id = basename(dirname($path));
            $name = basename($path);
            $uriPath = $this->router->generate('assets', ['type' => 'images', 'id' => $id, 'name' => $name]);
            return $this->site->uri($uriPath, includeLanguage: false);
        }

        if (Str::startsWith($path, $contentPath = FileSystem::normalizePath($this->config->getString('system.pages.path')))) {
            $uriPath = preg_replace('~[/\\\](\d+-)~', '/', Str::after(dirname($path), $contentPath))
                ?? throw new RuntimeException(sprintf('Replacement failed with error: %s', preg_last_error_msg()));
            return $this->site->uri(Path::join([$uriPath, basename($path)]), includeLanguage: false);
        }

        if (Str::startsWith($path, FileSystem::normalizePath($this->config->getString('system.users.paths.images')))) {
            $image = basename($path);
            $uriPath = $this->router->generate('panel.users.images', compact('image'));
            return $this->site->uri($uriPath, includeLanguage: false);
        }

        if (Str::startsWith($path, $panelAssetsPath = FileSystem::normalizePath($this->config->getString('system.panel.paths.assets')))) {
            $uriPath = Str::after($path, $panelAssetsPath);
            return $this->site->uri(Path::join(['panel/assets/', $uriPath]), includeLanguage: false);
        }

        throw new FileUriGenerationException(sprintf('Cannot generate uri for "%s": missing file generator', $file->name()));
    }

    public function generateAbsolute(File $file): string
    {
        return Uri::resolveRelative($this->generate($file), $this->request->absoluteUri());
    }
}
