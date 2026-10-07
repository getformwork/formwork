<?php

namespace Formwork\Tests\Unit\Fields;

use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

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

    public function testChangingAValueAfterValidationClearsTheValidationState(): void
    {
        $field = new Field('name', ['type' => 'text', 'required' => true, 'value' => 'Alice']);
        $field->validate();
        $this->assertTrue($field->isValidated());

        $field->set('value', '');

        $this->assertFalse($field->isValidated());
        $this->assertFalse($field->isValid());
    }
}
