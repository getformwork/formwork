<?php

namespace Formwork\Tests\Unit\Updater;

use Formwork\Tests\TestCase;
use Formwork\Updater\SemVer;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(SemVer::class)]
final class SemVerTest extends TestCase
{
    // Construction and parsing

    public function testDefaultVersionIsZero(): void
    {
        $this->assertSame('0.0.0', (string) new SemVer());
    }

    public function testComponentsAreExposed(): void
    {
        $version = new SemVer(1, 2, 3, 'beta.2', 'build.5');

        $this->assertSame(1, $version->major());
        $this->assertSame(2, $version->minor());
        $this->assertSame(3, $version->patch());
        $this->assertSame('beta.2', $version->prerelease());
        $this->assertSame('build.5', $version->buildMetadata());
        $this->assertSame('1.2.3-beta.2+build.5', (string) $version);
    }

    /**
     * @return iterable<string, array{int, int, int}>
     */
    public static function negativeComponents(): iterable
    {
        yield 'major' => [-1, 0, 0];
        yield 'minor' => [0, -1, 0];
        yield 'patch' => [0, 0, -1];
    }

    #[DataProvider('negativeComponents')]
    public function testNegativeComponentsAreRejected(int $major, int $minor, int $patch): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SemVer($major, $minor, $patch);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validVersions(): iterable
    {
        yield 'simple' => ['1.2.3', '1.2.3'];
        yield 'zero' => ['0.0.0', '0.0.0'];
        yield 'large numbers' => ['10.20.30', '10.20.30'];
        yield 'beta' => ['1.0.0-beta', '1.0.0-beta'];
        yield 'beta with number' => ['1.0.0-beta.11', '1.0.0-beta.11'];
        yield 'alpha' => ['1.0.0-alpha', '1.0.0-alpha'];
        yield 'dev' => ['2.0.0-dev', '2.0.0-dev'];
        yield 'release candidate' => ['1.0.0-RC.1', '1.0.0-RC.1'];
        yield 'build metadata' => ['1.0.0+20130313144700', '1.0.0+20130313144700'];
        yield 'prerelease and build' => ['1.0.0-beta+exp.sha.5114f85', '1.0.0-beta+exp.sha.5114f85'];
        yield 'hyphenated build' => ['1.0.0+21AF26D3--117B344092BD', '1.0.0+21AF26D3--117B344092BD'];
        yield 'short alpha alias' => ['1.0.0-a.1', '1.0.0-alpha.1'];
        yield 'short beta alias' => ['1.0.0-b.2', '1.0.0-beta.2'];
        yield 'lowercase rc alias' => ['1.0.0-rc.3', '1.0.0-RC.3'];
    }

    #[DataProvider('validVersions')]
    public function testValidVersionsAreParsedAndNormalized(string $input, string $expected): void
    {
        $this->assertSame($expected, (string) SemVer::fromString($input));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidVersions(): iterable
    {
        yield 'empty' => [''];
        yield 'single number' => ['1'];
        yield 'two components' => ['1.2'];
        yield 'four components' => ['1.2.3.4'];
        yield 'leading v' => ['v1.2.3'];
        yield 'leading zero major' => ['01.2.3'];
        yield 'leading zero minor' => ['1.02.3'];
        yield 'leading zero patch' => ['1.2.03'];
        yield 'negative' => ['-1.2.3'];
        yield 'empty prerelease' => ['1.2.3-'];
        yield 'empty build metadata' => ['1.2.3+'];
        yield 'leading zero numeric prerelease' => ['1.2.3-beta.01'];
        yield 'unknown prerelease tag' => ['1.2.3-snapshot'];
        yield 'spaces' => ['1.2.3 '];
        yield 'leading space' => [' 1.2.3'];
        yield 'trailing newline' => ["1.2.3\n"];
        yield 'text' => ['latest'];
        yield 'letters in core' => ['1.x.3'];
        yield 'underscore in prerelease' => ['1.2.3-beta_1'];
        yield 'range' => ['^1.2.3'];
        yield 'null byte' => ["1.2.3\0"];
    }

    #[DataProvider('invalidVersions')]
    public function testInvalidVersionsAreRejected(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        SemVer::fromString($input);
    }

    public function testRoundTrip(): void
    {
        foreach (['1.2.3', '0.0.1-beta.5', '4.5.6-RC.1+build.7', '9.9.9+meta'] as $version) {
            $this->assertSame($version, (string) SemVer::fromString($version));
        }
    }

    public function testPrereleaseTagsAreNormalized(): void
    {
        $this->assertSame('alpha', (new SemVer(1, 0, 0, 'a'))->prerelease());
        $this->assertSame('beta.1', (new SemVer(1, 0, 0, 'b.1'))->prerelease());
        $this->assertSame('RC', (new SemVer(1, 0, 0, 'rc'))->prerelease());
        $this->assertSame('pl', (new SemVer(1, 0, 0, 'p'))->prerelease());
        $this->assertSame('pl', (new SemVer(1, 0, 0, 'patch'))->prerelease());
    }

    public function testUnknownPrereleaseTagsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid prerelease tag');

        new SemVer(1, 0, 0, 'nightly');
    }

    // Derived versions

    public function testVersionCoreDropsPrereleaseAndMetadata(): void
    {
        $this->assertSame('1.2.3', (string) SemVer::fromString('1.2.3-beta.1+build')->versionCore());
    }

    public function testWithoutPrerelease(): void
    {
        $version = SemVer::fromString('1.2.3-beta.1+build')->withoutPrerelease();

        $this->assertSame('1.2.3+build', (string) $version);
        $this->assertFalse($version->isPrerelease());
    }

    public function testWithoutBuildMetadata(): void
    {
        $version = SemVer::fromString('1.2.3-beta.1+build')->withoutBuildMetadata();

        $this->assertSame('1.2.3-beta.1', (string) $version);
        $this->assertFalse($version->hasBuildMetadata());
    }

    public function testPrereleaseAndMetadataPredicates(): void
    {
        $this->assertTrue(SemVer::fromString('1.0.0-beta')->isPrerelease());
        $this->assertFalse(SemVer::fromString('1.0.0')->isPrerelease());
        $this->assertFalse(SemVer::fromString('1.0.0')->hasBuildMetadata());
        $this->assertTrue(SemVer::fromString('1.0.0+x')->hasBuildMetadata());
    }

    public function testNextVersionsResetLowerComponentsAndDropLabels(): void
    {
        $version = SemVer::fromString('1.2.3-beta.1+build');

        $this->assertSame('2.0.0', (string) $version->nextMajor());
        $this->assertSame('1.3.0', (string) $version->nextMinor());
        $this->assertSame('1.2.4', (string) $version->nextPatch());
    }

    public function testVersionsAreImmutable(): void
    {
        $version = SemVer::fromString('1.2.3-beta.1');

        $version->nextMajor();
        $version->withoutPrerelease();
        $version->versionCore();

        $this->assertSame('1.2.3-beta.1', (string) $version);
    }

    public function testComparableStringHasNoBuildMetadata(): void
    {
        $this->assertSame('1.2.3-beta.1', SemVer::fromString('1.2.3-beta.1+build')->toComparableString());
    }

    // Comparison

    /**
     * @return iterable<string, array{string, string, string, bool}>
     */
    public static function comparisons(): iterable
    {
        yield 'equal' => ['1.2.3', '1.2.3', '==', true];
        yield 'not equal' => ['1.2.3', '1.2.4', '!=', true];
        yield 'same is not different' => ['1.2.3', '1.2.3', '!=', false];
        yield 'less patch' => ['1.2.3', '1.2.4', '<', true];
        yield 'less minor' => ['1.2.9', '1.3.0', '<', true];
        yield 'less major' => ['1.9.9', '2.0.0', '<', true];
        yield 'greater' => ['2.0.0', '1.9.9', '>', true];
        yield 'less or equal' => ['1.0.0', '1.0.0', '<=', true];
        yield 'greater or equal' => ['1.0.1', '1.0.0', '>=', true];
        yield 'numeric not lexical' => ['1.10.0', '1.9.0', '>', true];
        yield 'double digits patch' => ['1.0.10', '1.0.9', '>', true];
        yield 'beta before release' => ['1.0.0-beta', '1.0.0', '<', true];
        yield 'alpha before beta' => ['1.0.0-alpha', '1.0.0-beta', '<', true];
        yield 'beta before rc' => ['1.0.0-beta', '1.0.0-rc', '<', true];
        yield 'rc before release' => ['1.0.0-rc.1', '1.0.0', '<', true];
        yield 'beta numbers' => ['1.0.0-beta.2', '1.0.0-beta.11', '<', true];
        yield 'prerelease numbers are numeric' => ['1.0.0-beta.11', '1.0.0-beta.2', '>', true];
        yield 'prerelease of next version is above the previous release' => ['1.0.1-beta', '1.0.0', '>', true];
        yield 'build metadata is ignored for equality' => ['1.0.0+a', '1.0.0+b', '==', true];
        yield 'build metadata is ignored for order' => ['1.0.0+z', '1.0.0+a', '<=', true];
        yield 'patch level is a prerelease' => ['1.0.0-pl', '1.0.0', '<', true];
    }

    #[DataProvider('comparisons')]
    public function testComparison(string $left, string $right, string $operator, bool $expected): void
    {
        $this->assertSame($expected, SemVer::fromString($left)->compareWith(SemVer::fromString($right), $operator));
        $this->assertSame($expected, SemVer::fromString($left)->compareWithString($right, $operator));
    }

    public function testInvalidOperatorsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid operator');

        SemVer::fromString('1.0.0')->compareWithString('1.0.0', '=>');
    }

    public function testVersionCompareOperatorAliasesAreNotAccepted(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SemVer::fromString('1.0.0')->compareWithString('1.0.0', 'lt');
    }

    public function testInvalidVersionStringsAreRejectedByCompareWithString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SemVer::fromString('1.0.0')->compareWithString('nope', '==');
    }

    // Tilde and caret

    /**
     * @return iterable<string, array{string, string, string, bool}>
     */
    public static function ranges(): iterable
    {
        yield '^ same version' => ['1.2.3', '1.2.3', '^', true];
        yield '^ later patch' => ['1.2.3', '1.2.9', '^', true];
        yield '^ later minor' => ['1.2.3', '1.9.0', '^', true];
        yield '^ next major' => ['1.2.3', '2.0.0', '^', false];
        yield '^ earlier version' => ['1.2.3', '1.2.2', '^', false];
        yield '^ previous major' => ['2.0.0', '1.9.9', '^', false];
        yield '^ prerelease of next major is excluded' => ['1.2.3', '2.0.0-beta', '^', false];
        yield '~ same version' => ['1.2.3', '1.2.3', '~', true];
        yield '~ later patch' => ['1.2.3', '1.2.9', '~', true];
        yield '~ next minor' => ['1.2.3', '1.3.0', '~', false];
        yield '~ earlier patch' => ['1.2.3', '1.2.2', '~', false];
        yield '~ next major' => ['1.2.3', '2.0.0', '~', false];
        yield '~ prerelease of next minor is excluded' => ['1.2.3', '1.3.0-beta', '~', false];
        yield '^ ignores build metadata' => ['1.2.3', '1.5.0+build', '^', true];
    }

    #[DataProvider('ranges')]
    public function testRangeOperators(string $base, string $candidate, string $operator, bool $expected): void
    {
        $this->assertSame($expected, SemVer::fromString($base)->compareWithString($candidate, $operator));
    }

    public function testPrereleasesAreNeverGreaterThanTheirRelease(): void
    {
        foreach (['alpha', 'beta', 'rc', 'dev', 'patch'] as $tag) {
            $prerelease = new SemVer(1, 0, 0, $tag);

            $this->assertTrue($prerelease->compareWith(new SemVer(1, 0, 0), '<'), sprintf('1.0.0-%s should precede 1.0.0', $tag));
        }
    }

    public function testHugeNumbersDoNotWrapAround(): void
    {
        $version = SemVer::fromString('1.2.99999999999999999999');

        $this->assertTrue($version->compareWithString('1.2.3', '>'));
    }
}
