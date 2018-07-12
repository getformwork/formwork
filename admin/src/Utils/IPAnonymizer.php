<?php

namespace Formwork\Admin\Utils;

use Exception;

class IPAnonymizer
{
    const IPV4_MASK = '255.255.255.0';

    const IPV6_MASK = 'ffff:ffff:ffff:ffff::';

    public static function anonymize($ip)
    {
        switch (strlen(inet_pton($ip))) {
            case 4:
                return static::anonymizeIPv4($ip);
            case 16:
                return static::anonymizeIPv6($ip);
            default:
                throw new Exception('Invalid IP address');
                break;
        }
    }

    public static function anonymizeIPv4($ip)
    {
        return inet_ntop(inet_pton($ip) & inet_pton(static::IPV4_MASK));
    }

    public static function anonymizeIPv6($ip)
    {
        return inet_ntop(inet_pton($ip) & inet_pton(static::IPV6_MASK));
    }
}
