<?php

namespace Formwork\Tests\Unit\Utils;

use Formwork\Tests\TestCase;
use Formwork\Utils\Constraint;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

#[CoversClass(Constraint::class)]
final class ConstraintTest extends TestCase
{
    public function testIsTruthyRecognizesTruthyValues(): void
    {
        $truthyValues = [true, 1, 'true', '1', 'on', 'yes'];
        foreach ($truthyValues as $truthyValue) {
            $this->assertTrue(Constraint::isTruthy($truthyValue));
        }

        $nonTruthyValues = [false, 0, 'false', '0', 'off', 'no', null, '', [], 2, 'random'];
        foreach ($nonTruthyValues as $nonTruthyValue) {
            $this->assertFalse(Constraint::isTruthy($nonTruthyValue));
        }
    }

    public function testIsFalsyRecognizesFalsyValues(): void
    {
        $falsyValues = [false, 0, 'false', '0', 'off', 'no'];
        foreach ($falsyValues as $falsyValue) {
            $this->assertTrue(Constraint::isFalsy($falsyValue));
        }

        $nonFalsyValues = [true, 1, 'true', '1', 'on', 'yes', null, '', [], 2, 'random'];
        foreach ($nonFalsyValues as $nonFalsyValue) {
            $this->assertFalse(Constraint::isFalsy($nonFalsyValue));
        }
    }

    public function testIsEmptyRecognizesEmptyValues(): void
    {
        $emptyValues = [null, '', []];
        foreach ($emptyValues as $emptyValue) {
            $this->assertTrue(Constraint::isEmpty($emptyValue));
        }

        $nonEmptyValues = [false, 0, 'false', '0', 'off', 'no', true, 1, 'true', '1', 'on', 'yes', 2, 'random'];
        foreach ($nonEmptyValues as $nonEmptyValue) {
            $this->assertFalse(Constraint::isEmpty($nonEmptyValue));
        }
    }

    public function testIsEqualToCanBeStrict(): void
    {
        // Strict comparison
        $this->assertTrue(Constraint::isEqualTo(1, 1, true));
        $this->assertFalse(Constraint::isEqualTo(1, '1', true));

        // Non-strict comparison
        $this->assertTrue(Constraint::isEqualTo(1, '1', false));
        $this->assertFalse(Constraint::isEqualTo(1, 2, false));
    }

    public function testIsNotEqualToCanBeStrict(): void
    {
        // Strict comparison
        $this->assertTrue(Constraint::isNotEqualTo(1, '1', true));
        $this->assertFalse(Constraint::isNotEqualTo(1, 1, true));

        // Non-strict comparison
        $this->assertTrue(Constraint::isNotEqualTo(1, 2, false));
        $this->assertFalse(Constraint::isNotEqualTo(1, '1', false));
    }

    public function testIsGreaterThanExcludesTheBound(): void
    {
        $this->assertTrue(Constraint::isGreaterThan(2, 1));
        $this->assertFalse(Constraint::isGreaterThan(1, 1));
        $this->assertFalse(Constraint::isGreaterThan(1, 2));
    }

    public function testIsGreaterThanOrEqualToIncludesTheBound(): void
    {
        $this->assertTrue(Constraint::isGreaterThanOrEqualTo(2, 1));
        $this->assertTrue(Constraint::isGreaterThanOrEqualTo(1, 1));
        $this->assertFalse(Constraint::isGreaterThanOrEqualTo(1, 2));
    }

    public function testIsLessThanExcludesTheBound(): void
    {
        $this->assertTrue(Constraint::isLessThan(1, 2));
        $this->assertFalse(Constraint::isLessThan(1, 1));
        $this->assertFalse(Constraint::isLessThan(2, 1));
    }

    public function testIsLessThanOrEqualToIncludesTheBound(): void
    {
        $this->assertTrue(Constraint::isLessThanOrEqualTo(1, 2));
        $this->assertTrue(Constraint::isLessThanOrEqualTo(1, 1));
        $this->assertFalse(Constraint::isLessThanOrEqualTo(2, 1));
    }

    #[DataProvider('uriProvider')]
    public function testIsUriRecognizesValidUris(string $value, bool $expected): void
    {
        $this->assertSame($expected, Constraint::isUri($value));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function uriProvider(): iterable
    {
        yield 'https with path query and fragment' => ['https://example.test/path?x=1#fragment', true];
        yield 'http with port' => ['http://localhost:8080/a', true];
        yield 'ftp' => ['ftp://host/file', true];
        yield 'mailto' => ['mailto:user@example.test', true];
        yield 'script scheme' => ['javascript:alert(1)', false];
        yield 'scheme relative' => ['//example.test', false];
        yield 'host only' => ['example.test', false];
        yield 'missing host' => ['http://', false];
        yield 'space in the host' => ['http://exa mple.com', false];
        yield 'empty' => ['', false];
    }

    #[DataProvider('emailProvider')]
    public function testIsEmailRecognizesValidAddresses(string $value, bool $expected): void
    {
        $this->assertSame($expected, Constraint::isEmail($value));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function emailProvider(): iterable
    {
        yield 'simple address' => ['user@example.test', true];
        yield 'tag and subdomain' => ['user+tag@sub.example.co', true];
        yield 'missing domain' => ['user@', false];
        yield 'missing local part' => ['@example.test', false];
        yield 'space' => ['user name@example.test', false];
        yield 'consecutive dots in the domain' => ['user@example..com', false];
        yield 'no at sign' => ['user.example.test', false];
        yield 'empty' => ['', false];
    }

    #[DataProvider('hostnameProvider')]
    public function testIsHostnameRecognizesValidHostnames(string $value, bool $expected): void
    {
        $this->assertSame($expected, Constraint::isHostname($value));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function hostnameProvider(): iterable
    {
        yield 'domain' => ['example.test', true];
        yield 'single label' => ['localhost', true];
        yield 'subdomain with a hyphen' => ['sub-domain.example.test', true];
        yield 'uppercase' => ['EXAMPLE.TEST', true];
        yield 'leading hyphen' => ['-bad.example.test', false];
        yield 'trailing hyphen' => ['bad-.example.test', false];
        yield 'underscore' => ['exa_mple.com', false];
        yield 'empty label' => ['example..com', false];
        yield 'label longer than 63 characters' => [str_repeat('a', 64) . '.com', false];
        yield 'empty' => ['', false];
    }

    public function testMatchesRegexChecksTheValue(): void
    {
        $this->assertTrue(Constraint::matchesRegex('hello', '/^h.*o$/'));
        $this->assertFalse(Constraint::matchesRegex('hello', '/^H.*O$/i'));
    }

    public function testMatchesWithoutEntireMatch(): void
    {
        $this->assertTrue(Constraint::matchesRegex('hello', '/e/', entireMatch: false));
        $this->assertFalse(Constraint::matchesRegex('hello', '/e/', entireMatch: true));
    }

    #[DataProvider('regexProvider')]
    public function testMatchesRegexesLikeHtmlInputPatterns(string $value, string $regex, bool $expected): void
    {
        $this->assertSame($expected, Constraint::matchesRegex($value, $regex));
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function regexProvider(): iterable
    {
        yield 'a simple regex' => ['abc', '[a-z]{2,3}', true];
        yield 'a partial match' => ['abcd', '[a-z]{2,3}', false];
        yield 'an alternation' => ['ab', 'a|ab', true];
        yield 'a slash' => ['1/2', '\d+/\d+', true];
        yield 'a leading slash' => ['/path', '/[a-z]+', true];
        yield 'a trailing slash' => ['path/', '[a-z]+/', true];
        yield 'a literal dollar' => ['5$', '\d+\$', true];
        yield 'a literal caret' => ['^5', '\^\d', true];
        yield 'a hash' => ['#ffffff', '#[0-9A-Fa-f]{6}', true];
        yield 'a parenthesis in a character class' => [')', '[)]', true];
        yield 'a tilde' => ['a~b', 'a~b', true];
        yield 'a final newline' => ["abc\n", '[a-z]+', false];
        yield 'unicode characters' => ['èéà', '\p{L}+', true];
        yield 'unicode characters with a dot' => ['è', '.', true];
        yield 'a value that is not valid UTF-8' => ["\xff", '.+', false];
        yield 'a regex enclosed in slashes' => ['abc', '/[a-z]+/', true];
        yield 'a regex enclosed in slashes with anchors' => ['abc', '/^[a-z]+$/', true];
        yield 'a regex enclosed in slashes with a final newline' => ["abc\n", '/^[a-z]+$/', false];
        yield 'a regex enclosed in slashes containing slashes' => ['1/2', '/\d+\/\d+/', true];
    }

    public function testInvalidRegexesAreReported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regex "(unclosed"');
        Constraint::matchesRegex('hello', '(unclosed');
    }

    public function testIsInRangeAcceptsReversedBoundsAndExclusions(): void
    {
        $this->assertTrue(Constraint::isInRange(5.25, 1, 10));
        $this->assertTrue(Constraint::isInRange(5, 1, 10));
        $this->assertFalse(Constraint::isInRange(11, 1, 10));

        $this->assertTrue(Constraint::isInRange(M_PI, 10, 1));
        $this->assertFalse(Constraint::isInRange(-1, 10, 1));

        $this->assertFalse(Constraint::isInRange(1, 1, 10, includeMin: false));
        $this->assertFalse(Constraint::isInRange(10, 1, 10, includeMax: false));
    }

    public function testIsInRangeAcceptsIntegerRanges(): void
    {
        $this->assertTrue(Constraint::isInIntegerRange(5, 1, 10));
        $this->assertFalse(Constraint::isInIntegerRange(11, 1, 10));
        $this->assertFalse(Constraint::isInIntegerRange(-1, 10, 1));

        $this->assertFalse(Constraint::isInIntegerRange(1, 1, 10, includeMin: false));
        $this->assertFalse(Constraint::isInIntegerRange(10, 1, 10, includeMax: false));
    }

    public function testIsInRangeHonoursTheStep(): void
    {
        $this->assertTrue(Constraint::isInIntegerRange(4, 0, 10, step: 2));
        $this->assertFalse(Constraint::isInIntegerRange(5, 0, 10, step: 2));
    }

    public function testIsOfTypeChecksTheType(): void
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

    public function testIsOfTypeAcceptsUnionTypes(): void
    {
        $this->assertTrue(Constraint::isOfType(123, 'int|string', unionTypes: true));
        $this->assertTrue(Constraint::isOfType('hello', 'int|string', unionTypes: true));
        $this->assertTrue(Constraint::isOfType(new stdClass(), 'array|stdClass', unionTypes: true));

        $this->assertFalse(Constraint::isOfType(12.34, 'int|string', unionTypes: true));
        $this->assertFalse(Constraint::isOfType(new stdClass(), 'int|string', unionTypes: true));
    }

    public function testHasKeysChecksEveryKey(): void
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

    #[DataProvider('ipProvider')]
    public function testIpValidatorsRecognizeTheirVersion(string $value, bool $ip, bool $ipv4, bool $ipv6): void
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
}
