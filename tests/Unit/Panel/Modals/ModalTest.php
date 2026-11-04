<?php

namespace Formwork\Tests\Unit\Panel\Modals;

use Formwork\Cms\App;
use Formwork\Fields\Field;
use Formwork\Fields\FieldFactory;
use Formwork\Model\Model;
use Formwork\Panel\Modals\Modal;
use Formwork\Panel\Modals\ModalButton;
use Formwork\Schemes\Schemes;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use Formwork\Translations\Translation;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(Modal::class)]
#[UsesApplication]
final class ModalTest extends TestCase
{
    public function testIdentifierGetsTheModalSuffix(): void
    {
        $this->assertSame('deleteModal', $this->modal('delete')->id());
    }

    public function testIdentifierSuffixIsNotDuplicated(): void
    {
        $this->assertSame('deleteModal', $this->modal('deleteModal')->id());
    }

    public function testTitleIsRequired(): void
    {
        $this->expectException(UnexpectedValueException::class);

        new Modal('modal', [], $this->translation(), $this->fieldFactory());
    }

    public function testDefaultsAreApplied(): void
    {
        $modal = $this->modal('modal');

        $this->assertNull($modal->message());
        $this->assertNull($modal->action());
        $this->assertNull($modal->target());
        $this->assertSame('small', $modal->size());
        $this->assertFalse($modal->isOpen());
        $this->assertTrue($modal->hasForm());
        $this->assertCount(0, $modal->buttons());
        $this->assertCount(0, $modal->fields());
    }

    public function testDataOverridesDefaults(): void
    {
        $modal = $this->modal('modal', [
            'message' => 'Message',
            'action'  => 'delete/',
            'target'  => 'page',
            'size'    => 'large',
            'open'    => true,
            'form'    => false,
        ]);

        $this->assertSame('Message', $modal->message());
        $this->assertSame('delete/', $modal->action());
        $this->assertSame('page', $modal->target());
        $this->assertSame('large', $modal->size());
        $this->assertTrue($modal->isOpen());
        $this->assertFalse($modal->hasForm());
    }

    public function testTitleAndMessageAreTranslated(): void
    {
        $modal = $this->modal('modal', [
            'title'   => '{{panel.modal.action.cancel}}',
            'message' => 'Press {{panel.modal.action.cancel}} to leave',
        ]);

        $this->assertSame('Cancel', $modal->title());
        $this->assertSame('Press Cancel to leave', $modal->message());
    }

    public function testUnknownTranslationKeysAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->modal('modal', ['title' => '{{panel.unknown.key}}']);
    }

    public function testModalCanBeOpenedAndClosed(): void
    {
        $modal = $this->modal('modal');

        $modal->open();
        $this->assertTrue($modal->isOpen());

        $modal->open(false);
        $this->assertFalse($modal->isOpen());
    }

    public function testTargetCanBeChanged(): void
    {
        $modal = $this->modal('modal', ['target' => 'first']);

        $modal->setTarget('second');
        $this->assertSame('second', $modal->target());

        $modal->setTarget(null);
        $this->assertNull($modal->target());
    }

    public function testButtonsAreCreatedFromTheData(): void
    {
        $modal = $this->modal('modal', [
            'buttons' => [
                'dismiss' => ['action' => 'dismiss', 'label' => '{{panel.modal.action.cancel}}'],
                'save'    => ['label' => 'Save'],
            ],
        ]);

        $buttons = $modal->buttons();

        $this->assertCount(2, $buttons);
        $this->assertInstanceOf(ModalButton::class, $dismiss = $buttons->get('dismiss'));
        $this->assertInstanceOf(ModalButton::class, $save = $buttons->get('save'));
        $this->assertSame('Cancel', $dismiss->label());
        $this->assertSame('Save', $save->label());
    }

    public function testButtonsAreCreatedOnlyOnce(): void
    {
        $modal = $this->modal('modal', ['buttons' => ['save' => ['label' => 'Save']]]);

        $this->assertSame($modal->buttons(), $modal->buttons());
    }

    public function testFieldsAreCreatedWithTheModalIdAsPrefix(): void
    {
        $modal = $this->modal('rename', [
            'fields' => [
                'name' => ['type' => 'text', 'label' => 'Name'],
            ],
        ]);

        $fields = $modal->fields();

        $this->assertCount(1, $fields);
        $this->assertInstanceOf(Field::class, $field = $fields->get('name'));
        $this->assertSame('renameModal.name', $field->name());
        $this->assertSame('text', $field->type());
    }

    public function testFieldsModelIsAppliedToTheFields(): void
    {
        $model = $this->createStub(Model::class);
        $modal = $this->modal('rename', ['fields' => ['name' => ['type' => 'text']]]);

        $modal->setFieldsModel($model);

        $this->assertSame($model, $modal->fields()->model());
    }

    public function testModalIsConvertedToAnArray(): void
    {
        $modal = $this->modal('modal', ['message' => 'Message']);

        $array = $modal->toArray();

        $this->assertSame('Title', $array['title']);
        $this->assertSame('Message', $array['message']);
        $this->assertSame('small', $array['size']);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function modal(string $id, array $data = []): Modal
    {
        return new Modal($id, $this->unchecked(['title' => 'Title', ...$data]), $this->translation(), $this->fieldFactory());
    }

    private function translation(): Translation
    {
        return new Translation('en', ['panel.modal.action.cancel' => 'Cancel']);
    }

    private function fieldFactory(): FieldFactory
    {
        $app = App::instance();
        $app->getService(Schemes::class);

        return $app->getService(FieldFactory::class);
    }
}
