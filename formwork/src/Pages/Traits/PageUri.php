<?php

namespace Formwork\Pages\Traits;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Utils\Path;
use Formwork\Utils\Uri;

trait PageUri
{
    protected App $app;

    /**
     * Get page or site route
     */
    abstract public function route(): ?string;

    /**
     * Get page or site canonical route
     */
    abstract public function canonicalRoute(): ?string;

    /**
     * Get the site the page belongs to
     */
    abstract public function site(): Site;

    /**
     * Return a URI relative to page
     */
    public function uri(string $path = '', bool|string $includeLanguage = true): string
    {
        $base = $this->app->request()->root();

        $route = $this->canonicalRoute() ?? $this->route();

        if ($includeLanguage) {
            $language = is_string($includeLanguage) ? $includeLanguage : $this->site()->languages()->current();

            $default = $this->site()->languages()->default();
            $preferred = $this->site()->languages()->preferred();

            if (($language !== null && $language !== $default) || ($preferred !== null && $preferred !== $default)) {
                return Path::join([$base, (string) $language, (string) $route, $path]);
            }
        }

        return Uri::make([], Path::join([$base, (string) $route, $path]));
    }

    /**
     * Get page absolute URI
     */
    public function absoluteUri(string $path = '', bool|string $includeLanguage = true): string
    {
        return Uri::resolveRelative($this->uri($path, $includeLanguage), $this->app->request()->absoluteUri());
    }
}
