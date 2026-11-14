<?php

namespace Formwork\Http\Utils;

use Formwork\Traits\StaticClass;
use InvalidArgumentException;

final class IpAnonymizer
{
    use StaticClass;

    /**
     * IPv4 addresses mask
     */
    private const string IPV4_MASK = '255.255.255.0';

    /**
     * IPv6 addresses mask
     */
    private const string IPV6_MASK = 'ffff:ffff:ffff:ffff::';

    /**
     * Anonymize an IP address
     */
    public static function anonymize(string $ip): string
    {
        return match (strlen(self::packIPAddress($ip))) {
            4       => self::anonymizeIPv4($ip),
            16      => self::anonymizeIPv6($ip),
            default => throw new InvalidArgumentException(sprintf('Invalid IP address %s', $ip)),
        };
    }

    /**
     * Anonymize an IPv4 address
     *
     * @throws InvalidArgumentException If the address is not an IPv4 address
     */
    public static function anonymizeIPv4(string $ip): string
    {
        $packedIp = self::packIPAddress($ip);

        if (strlen($packedIp) !== 4) {
            throw new InvalidArgumentException(sprintf('Invalid IPv4 address %s', $ip));
        }

        return self::unpackIPAddress($packedIp & self::packIPAddress(self::IPV4_MASK));
    }

    /**
     * Anonymize an IPv6 address
     *
     * @throws InvalidArgumentException If the address is not an IPv6 address
     */
    public static function anonymizeIPv6(string $ip): string
    {
        $packedIp = self::packIPAddress($ip);

        if (strlen($packedIp) !== 16) {
            throw new InvalidArgumentException(sprintf('Invalid IPv6 address %s', $ip));
        }

        return self::unpackIPAddress($packedIp & self::packIPAddress(self::IPV6_MASK));
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
