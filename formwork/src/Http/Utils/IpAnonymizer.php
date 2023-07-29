<?php

namespace Formwork\Http\Utils;

use Formwork\Traits\StaticClass;
use InvalidArgumentException;

class IpAnonymizer
{
    use StaticClass;

    /**
     * IPv4 addresses mask
     */
    protected const IPV4_MASK = '255.255.255.0';

    /**
     * IPv6 addresses mask
     */
    protected const IPV6_MASK = 'ffff:ffff:ffff:ffff::';

    /**
     * Anonymize an IP address
     */
    public static function anonymize(string $ip): string
    {
        switch (strlen(inet_pton($ip))) {
            case 4:
                return static::anonymizeIPv4($ip);
            case 16:
                return static::anonymizeIPv6($ip);
            default:
                throw new InvalidArgumentException(sprintf('Invalid IP address %s', $ip));
                break;
        }
    }

    /**
     * Anonymize an IPv4 address
     */
    public static function anonymizeIPv4(string $ip): string
    {
        return inet_ntop(inet_pton($ip) & inet_pton(self::IPV4_MASK));
    }

    /**
     * Anonymize an IPv6 address
     */
    public static function anonymizeIPv6(string $ip): string
    {
        return inet_ntop(inet_pton($ip) & inet_pton(self::IPV6_MASK));
    }
}
