<?php

namespace Formwork\Tests\Unit\Fields;

use Formwork\Cms\App;
use Formwork\Fields\Field;
use Formwork\Fields\FieldFactory;
use Formwork\Schemes\Schemes;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Field::class)]
#[UsesApplication]
final class FieldTypesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // `FieldFactory` is registered lazily by the schemes service loader
        App::instance()->getService(Schemes::class);
    }

    #[DataProvider('stringFieldTypeProvider')]
    public function testFieldsExpectingStringsRejectArrays(string $type): void
    {
        $field = App::instance()->getService(FieldFactory::class)->make('field', ['type' => $type]);

        // Like a value submitted as `field[]=value`
        $field->set('value', ['value']);

        $this->assertFalse($field->isValid());
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('numericValueProvider')]
    public function testNumericFieldsValidateMinimumMaximumAndStep(string $type, array $data, string $value, bool $expected): void
    {
        $field = App::instance()->getService(FieldFactory::class)->make('field', ['type' => $type, ...$data]);

        $field->set('value', $value);

        $this->assertSame($expected, $field->isValid());
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>, string, bool}>
     */
    public static function numericValueProvider(): iterable
    {
        foreach (['number', 'range', 'duration'] as $type) {
            yield "{$type}: a multiple of an integer step" => [$type, ['step' => 2], '4', true];
            yield "{$type}: a decimal value with an integer step" => [$type, ['step' => 1], '2.5', false];
            yield "{$type}: a multiple of a decimal step" => [$type, ['step' => 0.5], '1.5', true];
            // `0.3 / 0.1` is not exactly 3 in floating point arithmetic
            yield "{$type}: a multiple of a step not representable in floating point" => [$type, ['step' => 0.1], '0.3', true];
            yield "{$type}: a value that is not a multiple of a decimal step" => [$type, ['step' => 0.5], '1.25', false];
            yield "{$type}: a multiple of the step from the minimum" => [$type, ['min' => 0.25, 'step' => 0.5], '0.75', true];
            yield "{$type}: a multiple of the step not from the minimum" => [$type, ['min' => 0.25, 'step' => 0.5], '1', false];
            yield "{$type}: a negative multiple of the step" => [$type, ['step' => 0.5], '-1.5', true];
            yield "{$type}: a multiple of a very small step" => [$type, ['step' => 0.000001], '0.000003', true];
            yield "{$type}: a value that is not a multiple of a very small step" => [$type, ['step' => 0.000001], '0.0000035', false];
            yield "{$type}: a multiple of a very small step in exponential notation" => [$type, ['step' => 1e-10], '3e-10', true];
            yield "{$type}: a value that is not a multiple of a very small step in exponential notation" => [$type, ['step' => 1e-10], '3.5e-10', false];
            yield "{$type}: a multiple of a very small step from a very small minimum" => [$type, ['min' => 0.0000001, 'step' => 0.000001], '0.0000031', true];
            yield "{$type}: a value below a very small minimum" => [$type, ['min' => 0.000001], '0.0000005', false];
            // Like in browsers, steps that are not positive numbers fall back to 1
            yield "{$type}: an integer with a zero step" => [$type, ['step' => 0], '2', true];
            yield "{$type}: a decimal value with a zero step" => [$type, ['step' => 0], '2.5', false];
            yield "{$type}: a decimal value with a negative step" => [$type, ['step' => -1], '2.5', false];
            yield "{$type}: a value below a decimal minimum" => [$type, ['min' => 0.5], '0.25', false];
            yield "{$type}: a value above a decimal maximum" => [$type, ['max' => 1.5], '2', false];
            yield "{$type}: a value within decimal bounds" => [$type, ['min' => 0.5, 'max' => 1.5], '1', true];
        }
    }

    public function testStepMismatchErrorsShowDecimalSteps(): void
    {
        $field = App::instance()->getService(FieldFactory::class)->make('field', ['type' => 'number', 'step' => 0.5]);

        $field->set('value', '0.75');

        $this->assertStringContainsString('0.5', (string) $field->getValidationError());
    }

    #[DataProvider('patternValueProvider')]
    public function testFieldPatternsWorkWithAnyCharacter(string $pattern, string $value, bool $expected): void
    {
        $field = App::instance()->getService(FieldFactory::class)->make('field', ['type' => 'text', 'pattern' => $pattern]);

        $field->set('value', $value);

        $this->assertSame($expected, $field->isValid());
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function patternValueProvider(): iterable
    {
        yield 'a slash' => ['\d+/\d+', '1/2', true];
        yield 'a value not matching a pattern with a slash' => ['\d+/\d+', '12', false];
        yield 'a literal dollar' => ['\d+\$', '5$', true];
        yield 'a value not matching a pattern with a literal dollar' => ['\d+\$', '5', false];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('lengthProvider')]
    public function testLengthsAreCountedInCharacters(string $type, array $data, string $value, bool $expected): void
    {
        $field = App::instance()->getService(FieldFactory::class)->make('field', ['type' => $type, ...$data]);

        $field->set('value', $value);

        $this->assertSame($expected, $field->isValid());
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>, string, bool}>
     */
    public static function lengthProvider(): iterable
    {
        foreach (['text', 'textarea'] as $type) {
            yield "{$type}: accented characters within the maximum length" => [$type, ['maxLength' => 3], 'èèè', true];
            yield "{$type}: accented characters over the maximum length" => [$type, ['maxLength' => 3], 'èèèè', false];
            yield "{$type}: non-Latin characters within the maximum length" => [$type, ['maxLength' => 3], '日本語', true];
            yield "{$type}: accented characters below the minimum length" => [$type, ['minLength' => 4], 'èèè', false];
            yield "{$type}: accented characters at the minimum length" => [$type, ['minLength' => 3], 'èèè', true];
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function stringFieldTypeProvider(): iterable
    {
        yield 'email' => ['email'];
        yield 'color' => ['color'];
        yield 'date' => ['date'];
    }
}
