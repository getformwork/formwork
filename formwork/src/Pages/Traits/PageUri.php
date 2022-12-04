<?php

namespace Formwork\Pages\Traits;

use Formwork\Formwork;
use Formwork\Utils\HTTPRequest;
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

    /**
     * Return a URI relative to page
     */
    public function uri(string $path = '', bool|string $includeLanguage = true): string
    {
        $base = HTTPRequest::root();

        $route = $this->canonicalRoute() ?? $this->route();

        if ($includeLanguage) {
            $language = is_string($includeLanguage) ? $includeLanguage : Formwork::instance()->site()->languages()->current();

            $default = Formwork::instance()->site()->languages()->default();
            $preferred = Formwork::instance()->site()->languages()->preferred();

            if (($language !== null && $language !== $default) || ($preferred !== null && $preferred !== $default)) {
                return Path::join([$base, $language, $route, $path]);
            }
        }

        return Path::join([$base, $route, $path]);
    }

    /**
     * Get page absolute URI
     */
    public function absoluteUri(string $path = '', bool|string $includeLanguage = true): string
    {
        return Uri::resolveRelative($this->uri($path, $includeLanguage));
    }
}
