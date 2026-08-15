<?php

namespace Formwork\Http\Utils;

use Detection\MobileDetect;
use Formwork\Http\Request;
use Formwork\Traits\StaticClass;
use Formwork\Utils\Uri;
use InvalidArgumentException;
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
     * Get the source of the visitor based on the referer and host headers
     *
     * @return string|null The source of the visitor, an empty string if it is a direct visit, or null if it cannot be determined or is invalid
     *
     * @since 2.3.11
     */
    public static function getSource(Request $request): ?string
    {
        $referer = $request->referer();
        if ($referer === null || $referer === '') {
            return '';
        }

        try {
            $source = Uri::host($referer);
        } catch (InvalidArgumentException) {
            return null;
        }

        if ($source === null || filter_var($source, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return null;
        }

        $host = $request->host();
        if (
            $host === null || filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false
            || $source === $host // Source and host are already lowercased by `Uri::host()` and `Request::host()`
        ) {
            return null;
        }

        return $source;
    }
}
