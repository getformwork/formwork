<?php

namespace Formwork\Tests\Unit\Fields;

use Formwork\Exceptions\RecursionException;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Fields\FieldCollection;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translation;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(Field::class)]
final class FieldTest extends TestCase
{
    public function testSettingAValueInvalidatesPreviousValidation(): void
    {
        $field = new Field('name', ['required' => true, 'value' => 'Alice']);
        $field->validate();
        $this->assertTrue($field->isValidated());

        $field->set('value', 'Bob');

        $this->assertFalse($field->isValidated());
        $this->assertTrue($field->isValid());
    }

    public function testRemovingAValueInvalidatesPreviousValidation(): void
    {
        $field = new Field('name', ['type' => 'text', 'required' => true, 'value' => 'Alice']);
        $field->validate();
        $field->remove('value');

        $this->assertFalse($field->isValidated());
        $this->assertFalse($field->isValid());
    }

    public function testRequiredFieldCanTransitionFromValidToInvalidAndBackToValid(): void
    {
        $field = new Field('name', ['type' => 'text', 'required' => true, 'value' => 'Alice']);

        $this->assertTrue($field->isValid());
        $field->set('value', '');
        $this->assertFalse($field->isValid());
        $field->set('value', 'Bob');
        $this->assertTrue($field->isValid());
        $this->assertNull($field->getValidationError());
    }

    public function testValidationMethodCanNormalizeTheValue(): void
    {
        $field = new Field('name', ['type' => 'text', 'value' => '  Alice  ']);
        $field->setMethods([
            'validate' => static fn(Field $field, string $value): string => trim($value),
        ]);

        $field->validate();

        $this->assertSame('Alice', $field->value());
        $this->assertTrue($field->isValidated());
    }

    public function testValidationExceptionStillMarksTheFieldAsValidated(): void
    {
        $field = new Field('name', ['type' => 'text', 'required' => true]);

        try {
            $field->validate();
            $this->fail('The required field should have failed validation.');
        } catch (ValidationException) {
            // Expected.
        }

        $this->assertTrue($field->isValidated());
        $this->assertFalse($field->isValid());
    }

    public function testFieldsAreVisibleEnabledEditableAndOptionalByDefault(): void
    {
        $field = new Field('name', ['type' => 'text']);

        $this->assertTrue($field->isVisible());
        $this->assertFalse($field->isHidden());
        $this->assertFalse($field->isDisabled());
        $this->assertFalse($field->isReadonly());
        $this->assertFalse($field->isRequired());
    }

    public function testStateFlagsCanBeSetThroughTheFieldData(): void
    {
        $field = new Field('name', ['type' => 'text', 'visible' => false, 'disabled' => true, 'readonly' => true, 'required' => true]);

        $this->assertFalse($field->isVisible());
        $this->assertTrue($field->isHidden());
        $this->assertTrue($field->isDisabled());
        $this->assertTrue($field->isReadonly());
        $this->assertTrue($field->isRequired());
    }

    public function testFormNameUsesBracketNotationForDottedNames(): void
    {
        $this->assertSame('name', (new Field('name', ['type' => 'text']))->formName());
        $this->assertSame('user[address][city]', (new Field('user.address.city', ['type' => 'text']))->formName());
        $this->assertSame('custom', (new Field('name', ['type' => 'text', 'formName' => 'custom']))->formName());
    }

    public function testLabelFallsBackToTheFieldName(): void
    {
        $this->assertSame('name', (new Field('name', ['type' => 'text']))->label());
        $this->assertSame('Full name', (new Field('name', ['type' => 'text', 'label' => 'Full name']))->label());
    }

    public function testValueFallsBackToTheDefaultValue(): void
    {
        $field = new Field('name', ['type' => 'text', 'default' => 'Anonymous']);

        $this->assertSame('Anonymous', $field->value());
        $this->assertSame('Anonymous', $field->defaultValue());

        $field->set('value', 'Alice');

        $this->assertSame('Alice', $field->value());
        $this->assertSame('Anonymous', $field->defaultValue());
    }

    public function testFieldWithoutValueIsEmptyAndStringConversionUsesTheValue(): void
    {
        $field = new Field('name', ['type' => 'text']);

        $this->assertTrue($field->isEmpty());
        $this->assertSame('', (string) $field);

        $field->set('value', 'Alice');

        $this->assertFalse($field->isEmpty());
        $this->assertSame('Alice', (string) $field);
    }

    public function testFieldsCannotContainOtherFields(): void
    {
        $this->expectException(UnexpectedValueException::class);
        new Field('group', ['type' => 'text', 'fields' => []]);
    }

    public function testParentCollectionIsExposed(): void
    {
        $parent = new FieldCollection();

        $this->assertSame($parent, (new Field('name', ['type' => 'text'], $parent))->parent());
        $this->assertNull((new Field('name', ['type' => 'text']))->parent());
    }

    public function testTranslatableKeysAreInterpolatedWithTheCurrentTranslation(): void
    {
        $field = new Field('name', ['type' => 'text', 'label' => '{{field.name}}', 'placeholder' => 'Static text']);
        $field->setTranslation(new Translation('en', ['field.name' => 'Name']));

        $this->assertSame('Name', $field->label());
        $this->assertSame('Static text', $field->placeholder());
    }

    public function testLocalizedValuesAreSelectedByLanguage(): void
    {
        $field = new Field('name', ['type' => 'text', 'label' => ['en' => 'Name', 'it' => 'Nome']]);

        $field->setTranslation(new Translation('it', []));

        $this->assertSame('Nome', $field->label());
    }

    public function testValuesAndDefaultsAreNeverTranslated(): void
    {
        $field = new Field('name', ['type' => 'text', 'value' => '{{field.name}}', 'default' => '{{field.name}}']);
        $field->setTranslation(new Translation('en', ['field.name' => 'Name']));

        $this->assertSame('{{field.name}}', $field->value());
        $this->assertSame('{{field.name}}', $field->defaultValue());
    }

    public function testTranslationCanBeDisabledOrLimitedToSomeKeys(): void
    {
        $translation = new Translation('en', ['field.name' => 'Name']);

        $disabled = new Field('name', ['type' => 'text', 'label' => '{{field.name}}', 'translate' => false]);
        $disabled->setTranslation($translation);
        $this->assertSame('{{field.name}}', $disabled->label());

        $limited = new Field('name', ['type' => 'text', 'label' => '{{field.name}}', 'placeholder' => '{{field.name}}', 'translate' => ['placeholder']]);
        $limited->setTranslation($translation);
        $this->assertSame('{{field.name}}', $limited->label());
        $this->assertSame('Name', $limited->placeholder());
    }

    public function testValidationOfAFieldDependingOnItselfIsDetected(): void
    {
        $field = new Field('name', ['type' => 'text']);
        $field->setMethods([
            'validate' => static function (Field $field, mixed $value): mixed {
                $field->validate();

                return $value;
            },
        ]);

        $this->expectException(RecursionException::class);
        $field->validate();
    }
}
