<?php

namespace Formwork\Tests\Unit\Panel\Modals;

use Formwork\Panel\Modals\ModalButton;
use Formwork\Panel\Modals\ModalButtonCollection;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translation;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(ModalButton::class)]
#[CoversClass(ModalButtonCollection::class)]
final class ModalButtonTest extends TestCase
{
    public function testDefaultsAreApplied(): void
    {
        $button = new ModalButton(['label' => 'Save'], $this->translation());

        $this->assertSame('submit', $button->action());
        $this->assertSame('submit', $button->formType());
        $this->assertNull($button->icon());
        $this->assertSame('Save', $button->label());
        $this->assertSame('accent', $button->variant());
        $this->assertSame('left', $button->align());
        $this->assertNull($button->command());
    }

    public function testDataOverridesDefaults(): void
    {
        $button = new ModalButton([
            'action'  => 'command',
            'label'   => 'Go',
            'icon'    => 'check',
            'variant' => 'danger',
            'align'   => 'right',
            'command' => 'continue',
        ], $this->translation());

        $this->assertSame('command', $button->action());
        $this->assertSame('check', $button->icon());
        $this->assertSame('danger', $button->variant());
        $this->assertSame('right', $button->align());
        $this->assertSame('continue', $button->command());
    }

    public function testOnlySubmitButtonsSubmitTheForm(): void
    {
        $this->assertSame('button', (new ModalButton(['action' => 'dismiss', 'label' => 'Cancel'], $this->translation()))->formType());
        $this->assertSame('button', (new ModalButton(['action' => 'command', 'label' => 'Run'], $this->translation()))->formType());
        $this->assertSame('submit', (new ModalButton(['action' => 'submit', 'label' => 'Send'], $this->translation()))->formType());
    }

    public function testLabelIsRequired(): void
    {
        $this->expectException(UnexpectedValueException::class);

        new ModalButton(['action' => 'submit'], $this->translation());
    }

    public function testLabelIsTranslated(): void
    {
        $button = new ModalButton(['label' => '{{panel.modal.action.cancel}}'], $this->translation());

        $this->assertSame('Cancel', $button->label());
    }

    public function testLabelCanMixTextAndTranslations(): void
    {
        $button = new ModalButton(['label' => '{{panel.modal.action.cancel}} now'], $this->translation());

        $this->assertSame('Cancel now', $button->label());
    }

    public function testLabelWithoutPlaceholdersIsLeftUntouched(): void
    {
        $button = new ModalButton(['label' => 'Plain label'], $this->translation());

        $this->assertSame('Plain label', $button->label());
    }

    public function testButtonIsConvertedToAnArray(): void
    {
        $button = new ModalButton(['label' => 'Save', 'icon' => 'check'], $this->translation());

        $this->assertSame('Save', $button->toArray()['label']);
        $this->assertSame('check', $button->toArray()['icon']);
    }

    public function testButtonCollectionIsKeyedByName(): void
    {
        $collection = new ModalButtonCollection([
            'dismiss' => new ModalButton(['action' => 'dismiss', 'label' => 'Cancel'], $this->translation()),
            'save'    => new ModalButton(['label' => 'Save'], $this->translation()),
        ]);

        $this->assertSame(['dismiss', 'save'], array_keys($collection->toArray()));
        $this->assertInstanceOf(ModalButton::class, $save = $collection->get('save'));
        $this->assertSame('Save', $save->label());
    }

    private function translation(): Translation
    {
        return new Translation('en', ['panel.modal.action.cancel' => 'Cancel']);
    }
}
