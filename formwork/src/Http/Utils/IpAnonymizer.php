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
        return match (strlen(self::packIPAddress($ip))) {
            4       => static::anonymizeIPv4($ip),
            16      => static::anonymizeIPv6($ip),
            default => throw new InvalidArgumentException(sprintf('Invalid IP address %s', $ip)),
        };
    }

    /**
     * Anonymize an IPv4 address
     */
    public static function anonymizeIPv4(string $ip): string
    {
        return self::unpackIPAddress(self::packIPAddress($ip) & self::packIPAddress(self::IPV4_MASK));
    }

    /**
     * Anonymize an IPv6 address
     */
    public static function anonymizeIPv6(string $ip): string
    {
        return self::unpackIPAddress(self::packIPAddress($ip) & self::packIPAddress(self::IPV6_MASK));
    }

    private static function packIPAddress(string $ip): string
    {
        return inet_pton($ip) ?: throw new InvalidArgumentException('Cannot pack IP address');
    }

    private static function unpackIPAddress(string $ip): string
    {
        return inet_ntop($ip) ?: throw new InvalidArgumentException('Cannot unpack IP address');
    }
}
