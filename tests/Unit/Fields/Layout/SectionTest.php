<?php

namespace Formwork\Tests\Unit\Fields\Layout;

use Formwork\Fields\Layout\Section;
use Formwork\Fields\Translations\Translations;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Section::class)]
#[CoversTrait(Translations::class)]
final class SectionTest extends TestCase
{
    public function testNameIsReturned(): void
    {
        $this->assertSame('main', (new Section(['name' => 'main']))->name());
        $this->assertNull((new Section([]))->name());
    }

    public function testOrderDefaultsToTheLargestInteger(): void
    {
        $this->assertSame(PHP_INT_MAX, (new Section([]))->order());
        $this->assertSame(3, (new Section(['order' => 3]))->order());
    }

    public function testNumericStringOrderIsCastToAnInteger(): void
    {
        $this->assertSame(2, (new Section(['order' => '2']))->order());
    }

    public function testIsComparesToBooleanTrueOnly(): void
    {
        $section = new Section(['collapsible' => true, 'collapsed' => 'true', 'count' => 1]);

        $this->assertTrue($section->is('collapsible'));
        $this->assertFalse($section->is('collapsed'));
        $this->assertFalse($section->is('count'));
        $this->assertFalse($section->is('missing'));
        $this->assertTrue($section->is('missing', true));
    }

    public function testDataIsAvailable(): void
    {
        $section = new Section(['name' => 'main', 'fields' => ['a', 'b']]);

        $this->assertSame(['a', 'b'], $section->get('fields'));
        $this->assertSame(['order' => PHP_INT_MAX, 'name' => 'main', 'fields' => ['a', 'b']], $section->toArray());
    }

    // label() and translations

    public function testLabelDefaultsToTheName(): void
    {
        $this->assertSame('main', (new Section(['name' => 'main']))->label());
    }

    public function testLabelIsNullWithoutNameAndLabel(): void
    {
        $this->assertNull((new Section([]))->label());
    }

    public function testLabelIsReturnedVerbatimWithoutTranslation(): void
    {
        $this->assertSame('{{greeting}}', (new Section(['label' => '{{greeting}}']))->label());
    }

    public function testLabelInterpolatesTranslationStrings(): void
    {
        $section = new Section(['label' => '{{greeting}} there']);
        $section->setTranslation($this->translation('en'));

        $this->assertSame('Hello there', $section->label());
    }

    public function testLabelUsesTheLanguageSpecificValue(): void
    {
        $section = new Section(['label' => ['en' => 'Main', 'it' => 'Principale']]);
        $section->setTranslation($this->translation('it'));

        $this->assertSame('Principale', $section->label());
    }

    public function testLabelDoesNotChangeBetweenLanguagesOnceTranslationIsReplaced(): void
    {
        $section = new Section(['label' => '{{greeting}}']);
        $section->setTranslation($this->translation('en'));
        $this->assertSame('Hello', $section->label());

        $section->setTranslation($this->translation('it'));
        $this->assertSame('Ciao', $section->label());
    }

    public function testLabelDefinedOnlyForOtherLanguagesDoesNotBreak(): void
    {
        $section = new Section(['name' => 'main', 'label' => ['it' => 'Principale']]);
        $section->setTranslation($this->translation('en'));

        $this->assertIsString($section->label());
    }

    public function testLabelWithUnknownTranslationKeyDoesNotBreak(): void
    {
        $section = new Section(['label' => '{{no.such.key}}']);
        $section->setTranslation($this->translation('en'));

        $this->assertSame('{{no.such.key}}', $section->label());
    }

    public function testEscapedInterpolationSequencesAreNotTranslated(): void
    {
        $section = new Section(['label' => '\{{greeting}}']);
        $section->setTranslation($this->translation('en'));

        $this->assertSame('{{greeting}}', $section->label());
    }

    #[DataProvider('nonStringLabels')]
    public function testNonStringLabelsAreReturnedUntouched(mixed $label): void
    {
        $section = new Section(['label' => $label]);
        $section->setTranslation($this->translation('en'));

        $this->assertSame($label, $section->get('label'));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function nonStringLabels(): iterable
    {
        yield 'integer' => [42];
        yield 'bool' => [true];
        yield 'null' => [null];
    }

    private function translation(string $code): Translation
    {
        return new Translation($code, $code === 'it' ? ['greeting' => 'Ciao'] : ['greeting' => 'Hello']);
    }
}
