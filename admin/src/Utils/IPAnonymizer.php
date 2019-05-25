<?php

namespace Formwork\Admin\Utils;

use InvalidArgumentException;

class IPAnonymizer
{
    /**
     * IPv4 addresses mask
     *
     * @var string
     */
    protected const IPV4_MASK = '255.255.255.0';

    /**
     * IPv6 addresses mask
     *
     * @var string
     */
    protected const IPV6_MASK = 'ffff:ffff:ffff:ffff::';

    /**
     * Anonymize an IP address
     *
     * @param string $ip
     *
     * @return string
     */
    public static function anonymize($ip)
    {
        switch (strlen(inet_pton($ip))) {
            case 4:
                return static::anonymizeIPv4($ip);
            case 16:
                return static::anonymizeIPv6($ip);
            default:
                throw new InvalidArgumentException('Invalid IP address ' . $ip);
                break;
        }
    }

    /**
     * Anonymize an IPv4 address
     *
     * @param string $ip
     *
     * @return string
     */
    public static function anonymizeIPv4($ip)
    {
        return inet_ntop(inet_pton($ip) & inet_pton(static::IPV4_MASK));
    }

    /**
     * Anonymize an IPv6 address
     *
     * @param string $ip
     *
     * @return string
     */
    public static function anonymizeIPv6($ip)
    {
        return inet_ntop(inet_pton($ip) & inet_pton(static::IPV6_MASK));
    }
}
