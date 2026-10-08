<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Utils\IpAnonymizer;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(IpAnonymizer::class)]
final class IpAnonymizerTest extends TestCase
{
    #[DataProvider('addressProvider')]
    public function testAddressesAreAnonymizedKeepingOnlyTheNetworkPrefix(string $address, string $expected): void
    {
        $this->assertSame($expected, IpAnonymizer::anonymize($address));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function addressProvider(): iterable
    {
        yield 'IPv4' => ['192.0.2.123', '192.0.2.0'];
        yield 'IPv4 with the maximum host part' => ['255.255.255.255', '255.255.255.0'];
        yield 'IPv4 already anonymized' => ['192.0.2.0', '192.0.2.0'];
        yield 'IPv4 unspecified address' => ['0.0.0.0', '0.0.0.0'];
        yield 'full IPv6' => ['2001:db8:abcd:12:1234:5678:9abc:def0', '2001:db8:abcd:12::'];
        yield 'abbreviated IPv6' => ['2001:db8::1', '2001:db8::'];
        yield 'IPv6 loopback' => ['::1', '::'];
        yield 'IPv6 link local' => ['fe80::1ff:fe23:4567:890a', 'fe80::'];
    }

    public function testAnonymizationIsIdempotent(): void
    {
        foreach (['192.0.2.123', '2001:db8:abcd:12:1234:5678:9abc:def0'] as $address) {
            $once = IpAnonymizer::anonymize($address);

            $this->assertSame($once, IpAnonymizer::anonymize($once));
        }
    }

    #[DataProvider('invalidAddressProvider')]
    public function testInvalidIpAddressesAreRejected(string $address): void
    {
        $this->expectException(InvalidArgumentException::class);
        IpAnonymizer::anonymize($address);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidAddressProvider(): iterable
    {
        yield 'text' => ['invalid'];
        yield 'empty string' => [''];
        yield 'incomplete IPv4' => ['1.2.3'];
        yield 'out of range octet' => ['256.0.0.1'];
        yield 'trailing whitespace' => ['192.0.2.123 '];
        yield 'IPv4 with a port' => ['192.0.2.123:80'];
    }
}
