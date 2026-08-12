<?php

namespace Formwork\Http\Utils;

use Detection\MobileDetect;
use Formwork\Http\Request;
use Formwork\Traits\StaticClass;
use Formwork\Utils\Uri;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

final class Visitor
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
        return !self::isBot($request);
    }

    public static function getDeviceType(Request $request): DeviceType
    {
        static $mobileDetect = new MobileDetect(config: ['autoInitOfHttpHeaders' => false]);
        $mobileDetect->setUserAgent($request->userAgent() ?? '');
        return match (true) {
            $mobileDetect->isMobile() => DeviceType::Mobile,
            $mobileDetect->isTablet() => DeviceType::Tablet,
            default                   => DeviceType::Desktop,
        };
    }

    public static function isMobile(Request $request): bool
    {
        return self::getDeviceType($request) === DeviceType::Mobile;
    }

    public static function isTablet(Request $request): bool
    {
        return self::getDeviceType($request) === DeviceType::Tablet;
    }

    public static function isDesktop(Request $request): bool
    {
        return self::getDeviceType($request) === DeviceType::Desktop;
    }

    /**
     * Return the source of the visitor, if any
     * If the visitor has no source ("direct" visit), an empty string is returned
     * If the source is invalid or the same as the current host, null is returned
     */
    public static function getSource(Request $request): ?string
    {
        $referer = $request->referer();
        if ($referer === null) {
            return '';
        }

        $source = Uri::host($referer);

        if (filter_var($source, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false || $source === $request->host()) {
            return null;
        }

        return $source;
    }
}
