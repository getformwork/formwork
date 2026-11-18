<?php

namespace Formwork\Tests\Unit\Fields\Layout;

use Formwork\Fields\Layout\Section;
use Formwork\Fields\Layout\Tab;
use Formwork\Translations\Translation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests shared by sections and tabs of a layout, which behave the same way
 *
 * @mixin TestCase
 */
trait ProvidesLayoutItemTests
{
    public function testNameIsReturned(): void
    {
        $this->assertSame('main', ($this->layoutItem(['name' => 'main']))->name());
        $this->assertNull(($this->layoutItem([]))->name());
    }

    public function testOrderDefaultsToTheLargestInteger(): void
    {
        $this->assertSame(PHP_INT_MAX, ($this->layoutItem([]))->order());
        $this->assertSame(3, ($this->layoutItem(['order' => 3]))->order());
    }

    public function testNumericStringOrderIsCastToAnInteger(): void
    {
        $this->assertSame(2, ($this->layoutItem(['order' => '2']))->order());
    }

    public function testIsComparesToBooleanTrueOnly(): void
    {
        $item = $this->layoutItem(['collapsible' => true, 'collapsed' => 'true', 'count' => 1]);

        $this->assertTrue($item->is('collapsible'));
        $this->assertFalse($item->is('collapsed'));
        $this->assertFalse($item->is('count'));
        $this->assertFalse($item->is('missing'));
        $this->assertTrue($item->is('missing', true));
    }

    public function testDataIsAvailable(): void
    {
        $item = $this->layoutItem(['name' => 'main', 'fields' => ['a', 'b']]);

        $this->assertSame(['a', 'b'], $item->get('fields'));
        $this->assertSame(['order' => PHP_INT_MAX, 'name' => 'main', 'fields' => ['a', 'b']], $item->toArray());
    }

    // label() and translations

    public function testLabelDefaultsToTheName(): void
    {
        $this->assertSame('main', ($this->layoutItem(['name' => 'main']))->label());
    }

    public function testLabelIsNullWithoutNameAndLabel(): void
    {
        $this->assertNull(($this->layoutItem([]))->label());
    }

    public function testLabelIsReturnedVerbatimWithoutTranslation(): void
    {
        $this->assertSame('{{greeting}}', ($this->layoutItem(['label' => '{{greeting}}']))->label());
    }

    public function testLabelInterpolatesTranslationStrings(): void
    {
        $item = $this->layoutItem(['label' => '{{greeting}} there']);
        $item->setTranslation($this->translation('en'));

        $this->assertSame('Hello there', $item->label());
    }

    public function testLabelUsesTheLanguageSpecificValue(): void
    {
        $item = $this->layoutItem(['label' => ['en' => 'Main', 'it' => 'Principale']]);
        $item->setTranslation($this->translation('it'));

        $this->assertSame('Principale', $item->label());
    }

    public function testLabelDoesNotChangeBetweenLanguagesOnceTranslationIsReplaced(): void
    {
        $item = $this->layoutItem(['label' => '{{greeting}}']);
        $item->setTranslation($this->translation('en'));
        $this->assertSame('Hello', $item->label());

        $item->setTranslation($this->translation('it'));
        $this->assertSame('Ciao', $item->label());
    }

    public function testLabelDefinedOnlyForOtherLanguagesDoesNotBreak(): void
    {
        /** @todo */
        $this->markTestSkipped('This fallback behavior is not yet implemented since it is a breaking change.');

        // @phpstan-ignore deadCode.unreachable
        $item = $this->layoutItem(['name' => 'main', 'label' => ['it' => 'Principale']]);
        $item->setTranslation($this->translation('en'));

        $this->assertIsString($item->label());
    }

    public function testLabelWithUnknownTranslationKeyDoesNotBreak(): void
    {
        $item = $this->layoutItem(['label' => '{{no.such.key}}']);
        $item->setTranslation($this->translation('en'));

        $this->assertSame('{{no.such.key}}', $item->label());
    }

    public function testEscapedInterpolationSequencesAreNotTranslated(): void
    {
        $item = $this->layoutItem(['label' => '\{{greeting}}']);
        $item->setTranslation($this->translation('en'));

        $this->assertSame('{{greeting}}', $item->label());
    }

    #[DataProvider('nonStringLabelsProvider')]
    public function testNonStringLabelsAreReturnedUntouched(mixed $label): void
    {
        $item = $this->layoutItem(['label' => $label]);
        $item->setTranslation($this->translation('en'));

        $this->assertSame($label, $item->get('label'));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function nonStringLabelsProvider(): iterable
    {
        yield 'integer' => [42];
        yield 'bool' => [true];
        yield 'null' => [null];
    }

    /**
     * @param array<string, mixed> $data
     */
    abstract protected function layoutItem(array $data): Section|Tab;

    private function translation(string $code): Translation
    {
        return new Translation($code, $code === 'it' ? ['greeting' => 'Ciao'] : ['greeting' => 'Hello']);
    }
}
