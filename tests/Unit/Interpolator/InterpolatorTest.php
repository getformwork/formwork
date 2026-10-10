<?php

namespace Formwork\Tests\Unit\Interpolator;

use Formwork\Interpolator\Errors\SyntaxError;
use Formwork\Interpolator\Exceptions\InterpolationException;
use Formwork\Interpolator\Interpolator;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Interpolator\Fixtures\InterpolationTarget;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

#[CoversClass(Interpolator::class)]
final class InterpolatorTest extends TestCase
{
    public function testExpressionsAreTokenizedParsedAndInterpolated(): void
    {
        $vars = ['site' => ['title' => 'Formwork', 'languages' => ['en', 'it']]];

        $this->assertSame('Formwork', Interpolator::interpolate('site.title', $vars));
        $this->assertSame('it', Interpolator::interpolate('site.languages[1]', $vars));
        $this->assertSame(['en', 'it'], Interpolator::interpolate('site.languages', $vars));
    }

    public function testObjectsAndFunctionsCanBeCombined(): void
    {
        $vars = [
            'user'    => new InterpolationTarget(),
            'default' => static fn(mixed $value, mixed $fallback): mixed => $value ?? $fallback,
        ];

        $this->assertSame('public', Interpolator::interpolate('default(user.publicProperty, "none")', $vars));
        $this->assertSame('none', Interpolator::interpolate('default(user.nullProperty, "none")', $vars));
    }

    public function testNestedCallsAreEvaluatedFromTheInside(): void
    {
        $vars = ['add' => static fn(int $a, int $b): int => $a + $b];

        $this->assertSame(10, Interpolator::interpolate('add(add(1, 2), add(3, 4))', $vars));
    }

    public function testNumbersSeparatedByCommasWithoutSpacesAreSeparateArguments(): void
    {
        $vars = ['add' => static fn(int $a, int $b): int => $a + $b];

        $this->assertSame(3, Interpolator::interpolate('add(1,2)', $vars));
    }

    public function testArraysOfNumbersWithoutSpacesAreSupported(): void
    {
        $vars = ['identity' => static fn(array $value): array => $value];

        $this->assertSame([1, 2, 3], Interpolator::interpolate('identity([1,2,3])', $vars));
    }

    public function testStringsKeepTheirQuotes(): void
    {
        $vars = ['identity' => static fn(string $value): string => $value];

        $this->assertSame("'quoted'", Interpolator::interpolate('identity("\'quoted\'")', $vars));
    }

    #[DataProvider('syntaxErrorProvider')]
    public function testSyntaxErrorsAreThrown(string $expression): void
    {
        $this->expectException(SyntaxError::class);
        Interpolator::interpolate($expression, ['a' => 1]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function syntaxErrorProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'unexpected character' => ['a + 1'];
        yield 'literal' => ['1'];
        yield 'unclosed call' => ['a('];
        yield 'trailing tokens' => ['a b'];
    }

    public function testUndefinedVariablesAreInterpolationExceptions(): void
    {
        $this->expectException(InterpolationException::class);
        Interpolator::interpolate('missing', []);
    }

    public function testInterpolationExceptionsAreRuntimeExceptions(): void
    {
        $this->expectException(RuntimeException::class);
        Interpolator::interpolate('missing', []);
    }

    public function testVariablesAreNeverLeakedAcrossCalls(): void
    {
        Interpolator::interpolate('a', ['a' => 1]);

        $this->expectException(InterpolationException::class);
        Interpolator::interpolate('a', []);
    }

    public function testFunctionsOfTheGlobalScopeAreNotCallable(): void
    {
        $this->expectException(InterpolationException::class);
        Interpolator::interpolate('phpinfo()', []);
    }

    public function testStringsAreNeverTreatedAsCallables(): void
    {
        $vars = ['name' => 'phpinfo'];

        // A string value is a plain value even when it names a function, and arguments do not call it
        $this->assertSame('phpinfo', Interpolator::interpolate('name()', $vars));
        $this->assertSame('phpinfo', Interpolator::interpolate('name("argument")', $vars));
    }
}
