<?php

namespace Formwork\Http\Utils;

use Formwork\Http\Request;
use Formwork\Traits\StaticClass;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

class Visitor
{
    use StaticClass;

    /**
     * Return whether current visitor is a bot
     */
    public static function isBot(Request $request): bool
    {
        static $crawlerDetect = new CrawlerDetect();
        return $crawlerDetect->isCrawler($request->userAgent() ?? '');
    }

    /**
     * Return whether current user agent is a browser
     */
    public static function isBrowser(Request $request): bool
    {
        return !static::isBot($request);
    }

    /**
     * Detect whether current visitor prefers not to be tracked
     */
    public static function isTrackable(Request $request): bool
    {
        return $request->headers()->get('Dnt') !== '1';
    }
}
