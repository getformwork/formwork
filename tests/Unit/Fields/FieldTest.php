<?php

namespace Formwork\Tests\Unit\Fields;

use Formwork\Exceptions\RecursionException;
use Formwork\Fields\Dynamic\DynamicFieldValue;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Fields\FieldCollection;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translation;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use UnexpectedValueException;

#[CoversClass(Field::class)]
final class FieldTest extends TestCase
{
    public function testSettingAValueInvalidatesPreviousValidation(): void
    {
        $field = new Field('name', ['required' => true, 'value' => 'foo']);
        $field->validate();
        $this->assertTrue($field->isValidated());

        $field->set('value', 'bar');

        $this->assertFalse($field->isValidated());
        $this->assertTrue($field->isValid());
    }

    public function testRemovingAValueInvalidatesPreviousValidation(): void
    {
        $field = new Field('name', ['type' => 'text', 'required' => true, 'value' => 'foo']);
        $field->validate();
        $field->remove('value');

        $this->assertFalse($field->isValidated());
        $this->assertFalse($field->isValid());
    }

    public function testRequiredFieldCanTransitionFromValidToInvalidAndBackToValid(): void
    {
        $field = new Field('name', ['type' => 'text', 'required' => true, 'value' => 'foo']);

        $this->assertTrue($field->isValid());
        $field->set('value', '');
        $this->assertFalse($field->isValid());
        $field->set('value', 'bar');
        $this->assertTrue($field->isValid());
        $this->assertNull($field->getValidationError());
    }

    public function testValidationMethodCanNormalizeTheValue(): void
    {
        $field = new Field('name', ['type' => 'text', 'value' => '  foo  ']);
        $field->setMethods([
            'validate' => static fn(Field $field, string $value): string => trim($value),
        ]);

        $field->validate();

        $this->assertSame('foo', $field->value());
        $this->assertTrue($field->isValidated());
    }

    public function testValidationExceptionStillMarksTheFieldAsValidated(): void
    {
        $field = new Field('name', ['type' => 'text', 'required' => true]);

        $this->captureException(ValidationException::class, static fn() => $field->validate());

        $this->assertTrue($field->isValidated());
        $this->assertFalse($field->isValid());
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

    public function testFieldsAreVisibleEnabledEditableAndOptionalByDefault(): void
    {
        $field = new Field('name', ['type' => 'text']);

        $this->assertTrue($field->isVisible());
        $this->assertFalse($field->isHidden());
        $this->assertFalse($field->isDisabled());
        $this->assertFalse($field->isReadonly());
        $this->assertFalse($field->isRequired());
    }

    public function testFieldsCannotContainOtherFields(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Fields may not have other fields inside');

        new Field('group', ['type' => 'text', 'fields' => []]);
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

        $field->set('value', 'foo');

        $this->assertSame('foo', $field->value());
        $this->assertSame('Anonymous', $field->defaultValue());
    }

    public function testFieldWithoutValueIsEmptyAndStringConversionUsesTheValue(): void
    {
        $field = new Field('name', ['type' => 'text']);

        $this->assertTrue($field->isEmpty());
        $this->assertSame('', (string) $field);

        $field->set('value', 'foo');

        $this->assertFalse($field->isEmpty());
        $this->assertSame('foo', (string) $field);
    }

    public function testParentCollectionIsExposed(): void
    {
        $fieldCollection = new FieldCollection();

        $this->assertSame($fieldCollection, (new Field('name', ['type' => 'text'], $fieldCollection))->parent());
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

    public function testStringConversionUsesTheToStringMethodOfTheType(): void
    {
        $field = new Field('tags', ['type' => 'tags', 'value' => ['a', 'b']]);
        $field->setMethods(['toString' => static fn(Field $field): string => implode(', ', $field->value())]);

        $this->assertSame('a, b', (string) $field);
    }

    public function testSetValueMethodNormalizesTheValueWhenItIsSet(): void
    {
        $field = new Field('name', ['type' => 'text']);
        $field->setMethods(['setValue' => static fn(Field $field, string $value): string => strtolower($value)]);

        $field->set('value', 'FOO');

        $this->assertSame('foo', $field->value());
    }

    public function testKeysEndingWithAtAreComputedFromTheirExpression(): void
    {
        $loader = (new ReflectionProperty(DynamicFieldValue::class, 'varsLoader'))->isInitialized() ? DynamicFieldValue::$varsLoader : null;
        DynamicFieldValue::$varsLoader = static fn(): array => ['site' => ['title' => 'Formwork']];

        try {
            $field = new Field('name', ['type' => 'text']);
            $field->set('placeholder@', 'site.title');
            $field->set('value@', 'site.title');

            $this->assertSame('Formwork', $field->placeholder());
            // Validation computes the value before checking it
            $field->validate();
            $this->assertTrue($field->isValidated());
            $this->assertSame('Formwork', $field->value());
        } finally {
            if ($loader !== null) {
                DynamicFieldValue::$varsLoader = $loader;
            }
        }
    }

    public function testValuesThatAreNotStringsAreNotTranslated(): void
    {
        $field = new Field('name', ['type' => 'text', 'limit' => 10]);
        $field->setTranslation(new Translation('en', ['field.name' => 'Name']));

        $this->assertSame(10, $field->get('limit'));
    }
}
