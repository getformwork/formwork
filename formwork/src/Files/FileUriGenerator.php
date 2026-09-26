<?php

namespace Formwork\Files;

use Formwork\Cms\UriGenerator;
use Formwork\Config\Config;
use Formwork\Files\Exceptions\FileUriGenerationException;
use Formwork\Http\Request;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use RuntimeException;

class FileUriGenerator
{
    public function __construct(
        protected Config $config,
        protected Request $request,
        protected UriGenerator $uriGenerator,
    ) {}

    /**
     * Generate URI for the given File
     */
    public function generate(File $file): string
    {
        $path = $file->path();

        if (str_starts_with($path, FileSystem::normalizePath($this->config->getString('system.files.paths.site')))) {
            return $this->uriGenerator->route('files', ['name' => basename($path)]);
        }

        if (str_starts_with($path, FileSystem::normalizePath($this->config->getString('system.images.processPath')))) {
            return $this->uriGenerator->route('assets', ['type' => 'images', 'id' => basename(dirname($path)), 'name' => basename($path)]);
        }

        if (str_starts_with($path, $contentPath = FileSystem::normalizePath($this->config->getString('system.pages.path')))) {
            $uriPath = preg_replace('~[/\\\](\d+-)~', '/', Str::after(dirname($path), $contentPath))
                ?? throw new RuntimeException(sprintf('Replacement failed with error: %s', preg_last_error_msg()));
            return $this->uriGenerator->path(Path::join([$uriPath, basename($path)]));
        }

        if (str_starts_with($path, FileSystem::normalizePath($this->config->getString('system.users.paths.images')))) {
            return $this->uriGenerator->route('panel.users.images', ['image' => basename($path)]);
        }

        if (str_starts_with($path, $panelAssetsPath = FileSystem::normalizePath($this->config->getString('system.panel.paths.assets')))) {
            return $this->uriGenerator->path(Path::join(['panel/assets/', Str::after($path, $panelAssetsPath)]));
        }

        throw new FileUriGenerationException(sprintf('Cannot generate uri for "%s": missing file generator', $file->name()));
    }

    public function generateAbsolute(File $file): string
    {
        return Uri::resolveRelative($this->generate($file), $this->request->absoluteUri());
    }
}
