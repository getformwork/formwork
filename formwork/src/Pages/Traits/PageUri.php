<?php

namespace Formwork\Pages\Traits;

use Formwork\Formwork;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Path;
use Formwork\Utils\Uri;

trait PageUri
{
    /**
     * Page route
     */
    protected string $route;

    /**
     * Page absolute URI
     */
    protected string $absoluteUri;

    /**
     * Return a URI relative to page
     */
    public function uri(string $path = '', bool|string $includeLanguage = true): string
    {
        $base = HTTPRequest::root();

        $route = $this->canonical() ?? $this->route;

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
    public function absoluteUri(): string
    {
        if (isset($this->absoluteUri)) {
            return $this->absoluteUri;
        }
        return $this->absoluteUri = Uri::resolveRelative($this->uri());
    }
}
