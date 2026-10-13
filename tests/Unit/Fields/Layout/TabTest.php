<?php

namespace Formwork\Tests\Unit\Fields\Layout;

use Formwork\Fields\Layout\Tab;
use Formwork\Fields\Translations\Translations;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Tab::class)]
#[CoversTrait(Translations::class)]
final class TabTest extends TestCase
{
    public function testNameIsReturned(): void
    {
        $this->assertSame('main', (new Tab(['name' => 'main']))->name());
        $this->assertNull((new Tab([]))->name());
    }

    public function testOrderDefaultsToTheLargestInteger(): void
    {
        $this->assertSame(PHP_INT_MAX, (new Tab([]))->order());
        $this->assertSame(3, (new Tab(['order' => 3]))->order());
    }

    public function testNumericStringOrderIsCastToAnInteger(): void
    {
        $this->assertSame(2, (new Tab(['order' => '2']))->order());
    }

    public function testIsComparesToBooleanTrueOnly(): void
    {
        $tab = new Tab(['collapsible' => true, 'collapsed' => 'true', 'count' => 1]);

        $this->assertTrue($tab->is('collapsible'));
        $this->assertFalse($tab->is('collapsed'));
        $this->assertFalse($tab->is('count'));
        $this->assertFalse($tab->is('missing'));
        $this->assertTrue($tab->is('missing', true));
    }

    public function testDataIsAvailable(): void
    {
        $tab = new Tab(['name' => 'main', 'fields' => ['a', 'b']]);

        $this->assertSame(['a', 'b'], $tab->get('fields'));
        $this->assertSame(['order' => PHP_INT_MAX, 'name' => 'main', 'fields' => ['a', 'b']], $tab->toArray());
    }

    // label() and translations

    public function testLabelDefaultsToTheName(): void
    {
        $this->assertSame('main', (new Tab(['name' => 'main']))->label());
    }

    public function testLabelIsNullWithoutNameAndLabel(): void
    {
        $this->assertNull((new Tab([]))->label());
    }

    public function testLabelIsReturnedVerbatimWithoutTranslation(): void
    {
        $this->assertSame('{{greeting}}', (new Tab(['label' => '{{greeting}}']))->label());
    }

    public function testLabelInterpolatesTranslationStrings(): void
    {
        $tab = new Tab(['label' => '{{greeting}} there']);
        $tab->setTranslation($this->translation('en'));

        $this->assertSame('Hello there', $tab->label());
    }

    public function testLabelUsesTheLanguageSpecificValue(): void
    {
        $tab = new Tab(['label' => ['en' => 'Main', 'it' => 'Principale']]);
        $tab->setTranslation($this->translation('it'));

        $this->assertSame('Principale', $tab->label());
    }

    public function testLabelDoesNotChangeBetweenLanguagesOnceTranslationIsReplaced(): void
    {
        $tab = new Tab(['label' => '{{greeting}}']);
        $tab->setTranslation($this->translation('en'));
        $this->assertSame('Hello', $tab->label());

        $tab->setTranslation($this->translation('it'));
        $this->assertSame('Ciao', $tab->label());
    }

    public function testLabelDefinedOnlyForOtherLanguagesDoesNotBreak(): void
    {
        $tab = new Tab(['name' => 'main', 'label' => ['it' => 'Principale']]);
        $tab->setTranslation($this->translation('en'));

        $this->assertIsString($tab->label());
    }

    public function testLabelWithUnknownTranslationKeyDoesNotBreak(): void
    {
        $tab = new Tab(['label' => '{{no.such.key}}']);
        $tab->setTranslation($this->translation('en'));

        $this->assertSame('{{no.such.key}}', $tab->label());
    }

    public function testEscapedInterpolationSequencesAreNotTranslated(): void
    {
        $tab = new Tab(['label' => '\{{greeting}}']);
        $tab->setTranslation($this->translation('en'));

        $this->assertSame('{{greeting}}', $tab->label());
    }

    #[DataProvider('nonStringLabels')]
    public function testNonStringLabelsAreReturnedUntouched(mixed $label): void
    {
        $tab = new Tab(['label' => $label]);
        $tab->setTranslation($this->translation('en'));

        $this->assertSame($label, $tab->get('label'));
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
