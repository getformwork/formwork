<?php

namespace Formwork\Tests\Unit\Utils;

use Formwork\Tests\TestCase;
use Formwork\Utils\Constraint;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

#[CoversClass(Constraint::class)]
final class ConstraintTest extends TestCase
{
    public function testIsTruthy(): void
    {
        $truthyValues = [true, 1, 'true', '1', 'on', 'yes'];
        foreach ($truthyValues as $value) {
            $this->assertTrue(Constraint::isTruthy($value));
        }

        $nonTruthyValues = [false, 0, 'false', '0', 'off', 'no', null, '', [], 2, 'random'];
        foreach ($nonTruthyValues as $nonTruthyValue) {
            $this->assertFalse(Constraint::isTruthy($nonTruthyValue));
        }
    }

    public function testIsFalsy(): void
    {
        $falsyValues = [false, 0, 'false', '0', 'off', 'no'];
        foreach ($falsyValues as $value) {
            $this->assertTrue(Constraint::isFalsy($value));
        }

        $nonFalsyValues = [true, 1, 'true', '1', 'on', 'yes', null, '', [], 2, 'random'];
        foreach ($nonFalsyValues as $nonFalsyValue) {
            $this->assertFalse(Constraint::isFalsy($nonFalsyValue));
        }
    }

    public function testIsEmpty(): void
    {
        $emptyValues = [null, '', []];
        foreach ($emptyValues as $value) {
            $this->assertTrue(Constraint::isEmpty($value));
        }

        $nonEmptyValues = [false, 0, 'false', '0', 'off', 'no', true, 1, 'true', '1', 'on', 'yes', 2, 'random'];
        foreach ($nonEmptyValues as $nonEmptyValue) {
            $this->assertFalse(Constraint::isEmpty($nonEmptyValue));
        }
    }

    public function testIsEqual(): void
    {
        // Strict comparison
        $this->assertTrue(Constraint::isEqualTo(1, 1, true));
        $this->assertFalse(Constraint::isEqualTo(1, '1', true));

        // Non-strict comparison
        $this->assertTrue(Constraint::isEqualTo(1, '1', false));
        $this->assertFalse(Constraint::isEqualTo(1, 2, false));
    }

    public function testIsNotEqual(): void
    {
        // Strict comparison
        $this->assertTrue(Constraint::isNotEqualTo(1, '1', true));
        $this->assertFalse(Constraint::isNotEqualTo(1, 1, true));

        // Non-strict comparison
        $this->assertTrue(Constraint::isNotEqualTo(1, 2, false));
        $this->assertFalse(Constraint::isNotEqualTo(1, '1', false));
    }

    public function testIsGreaterThan(): void
    {
        $this->assertTrue(Constraint::isGreaterThan(2, 1));
        $this->assertFalse(Constraint::isGreaterThan(1, 1));
        $this->assertFalse(Constraint::isGreaterThan(1, 2));
    }

    public function testIsGreaterThanOrEqual(): void
    {
        $this->assertTrue(Constraint::isGreaterThanOrEqualTo(2, 1));
        $this->assertTrue(Constraint::isGreaterThanOrEqualTo(1, 1));
        $this->assertFalse(Constraint::isGreaterThanOrEqualTo(1, 2));
    }

    public function testIsLessThan(): void
    {
        $this->assertTrue(Constraint::isLessThan(1, 2));
        $this->assertFalse(Constraint::isLessThan(1, 1));
        $this->assertFalse(Constraint::isLessThan(2, 1));
    }

    public function testIsLessThanOrEqual(): void
    {
        $this->assertTrue(Constraint::isLessThanOrEqualTo(1, 2));
        $this->assertTrue(Constraint::isLessThanOrEqualTo(1, 1));
        $this->assertFalse(Constraint::isLessThanOrEqualTo(2, 1));
    }

    public function testMatches(): void
    {
        $this->assertTrue(Constraint::matchesRegex('hello', '/^h.*o$/'));
        $this->assertFalse(Constraint::matchesRegex('hello', '/^H.*O$/i'));
    }

    public function testMatchesWithoutEntireMatch(): void
    {
        $this->assertTrue(Constraint::matchesRegex('hello', '/e/', entireMatch: false));
        $this->assertFalse(Constraint::matchesRegex('hello', '/e/', entireMatch: true));
    }

    public function testIsInRange(): void
    {
        $this->assertTrue(Constraint::isInRange(5.25, 1, 10));
        $this->assertTrue(Constraint::isInRange(5, 1, 10));
        $this->assertFalse(Constraint::isInRange(11, 1, 10));

        $this->assertTrue(Constraint::isInRange(M_PI, 10, 1));
        $this->assertFalse(Constraint::isInRange(-1, 10, 1));

        $this->assertFalse(Constraint::isInRange(1, 1, 10, includeMin: false));
        $this->assertFalse(Constraint::isInRange(10, 1, 10, includeMax: false));
    }

    public function testIsInRangeWithIntegerRange(): void
    {
        $this->assertTrue(Constraint::isInIntegerRange(5, 1, 10));
        $this->assertFalse(Constraint::isInIntegerRange(11, 1, 10));
        $this->assertFalse(Constraint::isInIntegerRange(-1, 10, 1));

        $this->assertFalse(Constraint::isInIntegerRange(1, 1, 10, includeMin: false));
        $this->assertFalse(Constraint::isInIntegerRange(10, 1, 10, includeMax: false));
    }

    public function testIsInRangeWithStep(): void
    {
        $this->assertTrue(Constraint::isInIntegerRange(4, 0, 10, step: 2));
        $this->assertFalse(Constraint::isInIntegerRange(5, 0, 10, step: 2));
    }

    public function testIsType(): void
    {
        $this->assertTrue(Constraint::isOfType(123, 'int'));
        $this->assertTrue(Constraint::isOfType('hello', 'string'));
        $this->assertTrue(Constraint::isOfType([], 'array'));
        $this->assertTrue(Constraint::isOfType(12.34, 'float'));
        $this->assertTrue(Constraint::isOfType(true, 'bool'));
        $this->assertTrue(Constraint::isOfType(new stdClass(), 'stdClass'));

        $this->assertFalse(Constraint::isOfType(123, 'string'));
        $this->assertFalse(Constraint::isOfType('hello', 'array'));
        $this->assertFalse(Constraint::isOfType([], 'int'));
        $this->assertFalse(Constraint::isOfType(12.34, 'bool'));
        $this->assertFalse(Constraint::isOfType(true, 'float'));
        $this->assertFalse(Constraint::isOfType(new stdClass(), 'array'));
    }

    public function testIsTypeWithUnionTypes(): void
    {
        $this->assertTrue(Constraint::isOfType(123, 'int|string', unionTypes: true));
        $this->assertTrue(Constraint::isOfType('hello', 'int|string', unionTypes: true));
        $this->assertTrue(Constraint::isOfType(new stdClass(), 'array|stdClass', unionTypes: true));

        $this->assertFalse(Constraint::isOfType(12.34, 'int|string', unionTypes: true));
        $this->assertFalse(Constraint::isOfType(new stdClass(), 'int|string', unionTypes: true));
    }

    public function testHasKeys(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $this->assertTrue(Constraint::hasKeys($array, ['a', 'b']));
        $this->assertTrue(Constraint::hasKeys($array, []));
        $this->assertFalse(Constraint::hasKeys($array, ['a', 'd']));
    }

    public function testRangesWithoutALowerBoundAcceptNegativeNumbersAndZero(): void
    {
        $this->assertTrue(Constraint::isInRange(-5, end: 10));
        $this->assertTrue(Constraint::isInRange(0, end: 10));
        $this->assertTrue(Constraint::isInRange(-1.5, end: 10));
        $this->assertFalse(Constraint::isInRange(11, end: 10));
    }

    public function testRangesWithoutAnUpperBoundAcceptEveryNumberAboveTheLowerOne(): void
    {
        $this->assertTrue(Constraint::isInRange(1_000_000, start: 10));
        $this->assertTrue(Constraint::isInRange(10, start: 10));
        $this->assertFalse(Constraint::isInRange(9, start: 10));
    }

    public function testRangesWithoutBoundsAcceptEveryNumber(): void
    {
        foreach ([-1_000_000, -1.5, 0, 0.5, 42, PHP_INT_MAX] as $value) {
            $this->assertTrue(Constraint::isInRange($value), (string) $value);
            $this->assertTrue(Constraint::isInIntegerRange((int) $value), (string) $value);
        }
    }

    public function testRangeBoundsAreInclusiveByDefault(): void
    {
        $this->assertTrue(Constraint::isInRange(1, 1, 10));
        $this->assertTrue(Constraint::isInRange(10, 1, 10));
        $this->assertTrue(Constraint::isInIntegerRange(1, 1, 10));
        $this->assertTrue(Constraint::isInIntegerRange(10, 1, 10));
    }

    public function testRangeBoundsCanBeExcluded(): void
    {
        $this->assertTrue(Constraint::isInRange(5, 1, 10, includeMin: false, includeMax: false));
        $this->assertFalse(Constraint::isInRange(1, 1, 10, includeMin: false, includeMax: false));
        $this->assertFalse(Constraint::isInRange(10, 1, 10, includeMin: false, includeMax: false));
        $this->assertFalse(Constraint::isInRange(5, 5, 5, includeMin: false));
        $this->assertTrue(Constraint::isInRange(5, 5, 5));
    }

    public function testInclusivityFlagsRefersToTheLowerAndUpperBoundsAfterSwappingReversedOnes(): void
    {
        $this->assertTrue(Constraint::isInRange(10, 10, 1));
        $this->assertTrue(Constraint::isInRange(10, 10, 1, includeMin: false));
        $this->assertFalse(Constraint::isInRange(1, 10, 1, includeMin: false));
        $this->assertFalse(Constraint::isInRange(10, 10, 1, includeMax: false));
        $this->assertTrue(Constraint::isInRange(1, 10, 1, includeMax: false));
    }

    public function testIntegerRangesWithStepCountFromTheLowestBound(): void
    {
        $this->assertTrue(Constraint::isInIntegerRange(7, 1, 10, step: 3));
        $this->assertTrue(Constraint::isInIntegerRange(10, 1, 10, step: 3));
        $this->assertFalse(Constraint::isInIntegerRange(8, 1, 10, step: 3));
        $this->assertTrue(Constraint::isInIntegerRange(-3, -9, 9, step: 3));
        $this->assertFalse(Constraint::isInIntegerRange(-2, -9, 9, step: 3));
        $this->assertTrue(Constraint::isInIntegerRange(7, 10, 1, step: 3));
    }

    #[DataProvider('uriProvider')]
    public function testIsUri(string $value, bool $expected): void
    {
        $this->assertSame($expected, Constraint::isUri($value));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function uriProvider(): iterable
    {
        yield 'https with path query and fragment' => ['https://example.com/path?x=1#fragment', true];
        yield 'http with port' => ['http://localhost:8080/a', true];
        yield 'ftp' => ['ftp://host/file', true];
        yield 'mailto' => ['mailto:user@example.com', true];
        yield 'script scheme' => ['javascript:alert(1)', false];
        yield 'scheme relative' => ['//example.com', false];
        yield 'host only' => ['example.com', false];
        yield 'missing host' => ['http://', false];
        yield 'space in the host' => ['http://exa mple.com', false];
        yield 'empty' => ['', false];
    }

    #[DataProvider('emailProvider')]
    public function testIsEmail(string $value, bool $expected): void
    {
        $this->assertSame($expected, Constraint::isEmail($value));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function emailProvider(): iterable
    {
        yield 'simple address' => ['user@example.com', true];
        yield 'tag and subdomain' => ['user+tag@sub.example.co', true];
        yield 'missing domain' => ['user@', false];
        yield 'missing local part' => ['@example.com', false];
        yield 'space' => ['user name@example.com', false];
        yield 'consecutive dots in the domain' => ['user@example..com', false];
        yield 'no at sign' => ['user.example.com', false];
        yield 'empty' => ['', false];
    }

    #[DataProvider('ipProvider')]
    public function testIpValidators(string $value, bool $ip, bool $ipv4, bool $ipv6): void
    {
        $this->assertSame($ip, Constraint::isIp($value));
        $this->assertSame($ipv4, Constraint::isIpv4($value));
        $this->assertSame($ipv6, Constraint::isIpv6($value));
    }

    /**
     * @return iterable<string, array{string, bool, bool, bool}>
     */
    public static function ipProvider(): iterable
    {
        yield 'IPv4' => ['127.0.0.1', true, true, false];
        yield 'IPv4 unspecified address' => ['0.0.0.0', true, true, false];
        yield 'IPv6 loopback' => ['::1', true, false, true];
        yield 'abbreviated IPv6' => ['2001:db8::1', true, false, true];
        yield 'octet out of range' => ['256.0.0.1', false, false, false];
        yield 'incomplete IPv4' => ['1.2.3', false, false, false];
        yield 'leading zeros' => ['192.168.001.001', false, false, false];
        yield 'two abbreviations in IPv6' => ['1::2::3', false, false, false];
        yield 'empty' => ['', false, false, false];
    }

    #[DataProvider('hostnameProvider')]
    public function testIsHostname(string $value, bool $expected): void
    {
        $this->assertSame($expected, Constraint::isHostname($value));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function hostnameProvider(): iterable
    {
        yield 'domain' => ['example.com', true];
        yield 'single label' => ['localhost', true];
        yield 'subdomain with a hyphen' => ['sub-domain.example.com', true];
        yield 'uppercase' => ['EXAMPLE.COM', true];
        yield 'leading hyphen' => ['-bad.example.com', false];
        yield 'trailing hyphen' => ['bad-.example.com', false];
        yield 'underscore' => ['exa_mple.com', false];
        yield 'empty label' => ['example..com', false];
        yield 'label longer than 63 characters' => [str_repeat('a', 64) . '.com', false];
        yield 'empty' => ['', false];
    }
}
