<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Utils\IpAnonymizer;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(IpAnonymizer::class)]
final class IpAnonymizerTest extends TestCase
{
    public function testIpv4AndIpv6AddressesAreAnonymized(): void
    {
        $this->assertSame('192.0.2.0', IpAnonymizer::anonymize('192.0.2.123'));
        $this->assertSame('2001:db8:abcd:12::', IpAnonymizer::anonymize('2001:db8:abcd:12:1234:5678:9abc:def0'));
    }

    public function testInvalidIpAddressesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IpAnonymizer::anonymize('invalid');
    }
}
