<?php

namespace Formwork\Pages\Traits;

use Formwork\App;
use Formwork\Pages\Site;
use Formwork\Utils\Path;
use Formwork\Utils\Uri;

trait PageUri
{
    /**
     * Get page or site route
     */
    abstract public function route(): ?string;

    /**
     * Get page or site canonical route
     */
    abstract public function canonicalRoute(): ?string;

    abstract public function site(): Site;

    /**
     * Return a URI relative to page
     */
    public function uri(string $path = '', bool|string $includeLanguage = true): string
    {
        $base = App::instance()->request()->root();

        $route = $this->canonicalRoute() ?? $this->route();

        if ($includeLanguage) {
            $language = is_string($includeLanguage) ? $includeLanguage : $this->site()->languages()->current();

            $default = $this->site()->languages()->default();
            $preferred = $this->site()->languages()->preferred();

            if (($language !== null && $language !== $default) || ($preferred !== null && $preferred !== $default)) {
                return Path::join([$base, $language, $route, $path]);
            }
        }

        return Uri::make([], Path::join([$base, $route, $path]));
    }

    /**
     * Get page absolute URI
     */
    public function absoluteUri(string $path = '', bool|string $includeLanguage = true): string
    {
        return Uri::resolveRelative($this->uri($path, $includeLanguage));
    }
}
