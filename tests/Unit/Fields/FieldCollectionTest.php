<?php

namespace Formwork\Tests\Unit\Fields;

use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Fields\FieldCollection;
use Formwork\Fields\Layout\Layout;
use Formwork\Http\Request;
use Formwork\Http\RequestMethod;
use Formwork\Model\Model;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;

#[CoversClass(FieldCollection::class)]
class FieldCollectionTest extends TestCase
{
    public function testSetLayoutAndGetLayout(): void
    {
        $fieldCollection = new FieldCollection();
        $layout = $this->createStub(Layout::class);

        $fieldCollection->setLayout($layout);

        $this->assertSame($layout, $fieldCollection->layout());
    }

    public function testSetModelAndGetModel(): void
    {
        $fieldCollection = new FieldCollection();
        $model = $this->createStub(Model::class);

        $fieldCollection->setModel($model);

        $this->assertSame($model, $fieldCollection->model());
    }

    public function testModelIsNullByDefault(): void
    {
        $fieldCollection = new FieldCollection();

        $this->assertNull($fieldCollection->model());
    }

    public function testExtractReturnsFieldValuesWithKeys(): void
    {
        $fieldCollection = new FieldCollection([
            'name' => $this->createField('name', [
                'type'  => 'text',
                'value' => 'Sempronius',
            ]),
            'email' => $this->createField('email', [
                'type'  => 'email',
                'value' => 'sempronius@example.com',
            ]),
        ]);

        $this->assertSame(
            [
                'name'  => 'Sempronius',
                'email' => 'sempronius@example.com',
            ],
            $this->values($fieldCollection),
        );
    }

    public function testExtractUsesDefaultForMissingKeys(): void
    {
        $fieldCollection = new FieldCollection([
            'name'  => $this->createField('name', ['type' => 'text']),
            'email' => $this->createField('email', ['type' => 'email']),
        ]);

        $this->assertSame(
            [
                'name'  => 'fallback',
                'email' => 'fallback',
            ],
            $this->values($fieldCollection, 'missing', 'fallback'),
        );
    }

    public function testSetValuesReturnsSameCollection(): void
    {
        $fieldCollection = new FieldCollection([
            'name' => $this->createField('name', ['type' => 'text']),
        ]);

        $this->assertSame($fieldCollection, $fieldCollection->setValues(['name' => 'Sempronius']));
    }

    public function testSetValuesSetsValuesForFieldsPresentInData(): void
    {
        $fieldCollection = new FieldCollection([
            'name'  => $this->createField('name', ['type' => 'text']),
            'email' => $this->createField('email', ['type' => 'email']),
        ]);

        $fieldCollection->setValues([
            'name'  => 'Sempronius',
            'email' => 'sempronius@example.com',
        ]);

        $this->assertSame(
            [
                'name'  => 'Sempronius',
                'email' => 'sempronius@example.com',
            ],
            $this->values($fieldCollection),
        );
    }

    public function testSetValuesDoesNotChangeFieldsMissingFromData(): void
    {
        $fieldCollection = new FieldCollection([
            'name' => $this->createField('name', [
                'type'  => 'text',
                'value' => 'Existing name',
            ]),
            'email' => $this->createField('email', [
                'type'  => 'email',
                'value' => 'existing@example.com',
            ]),
        ]);

        $fieldCollection->setValues(['name' => 'Sempronius']);

        $this->assertSame(
            [
                'name'  => 'Sempronius',
                'email' => 'existing@example.com',
            ],
            $this->values($fieldCollection),
        );
    }

    public function testSetValuesUsesExplicitDefaultForMissingFields(): void
    {
        $fieldCollection = new FieldCollection([
            'name' => $this->createField('name', [
                'type'  => 'text',
                'value' => 'Existing name',
            ]),
            'email' => $this->createField('email', [
                'type'  => 'email',
                'value' => 'existing@example.com',
            ]),
        ]);

        $fieldCollection->setValues(['name' => 'Sempronius'], null);

        $this->assertSame(
            [
                'name'  => 'Sempronius',
                'email' => null,
            ],
            $this->values($fieldCollection),
        );
    }

    #[TestWith(['enabled', false], "'enabled' => false")]
    #[TestWith(['nullable', null], "'nullable' => null")]
    #[TestWith(['empty', ''], "'empty' => ''")]
    public function testSetValuesPreservesValuesThatArePresent(string $name, mixed $value): void
    {
        $fieldCollection = new FieldCollection([
            $name => $this->createField($name, [
                'type'  => 'text',
                'value' => 'original',
            ]),
        ]);

        $fieldCollection->setValues([$name => $value]);

        $this->assertSame([$name => $value], $this->values($fieldCollection));
    }

    public function testSetValuesFromRequestSetsRequestValues(): void
    {
        $fieldCollection = new FieldCollection([
            'name'  => $this->createField('name', ['type' => 'text']),
            'email' => $this->createField('email', ['type' => 'email']),
        ]);

        $request = $this->createRequest(RequestMethod::POST, input: [
            'name'  => 'Sempronius',
            'email' => 'sempronius@example.com',
        ]);

        $this->assertSame($fieldCollection, $fieldCollection->setValuesFromRequest($request));

        $this->assertSame(
            [
                'name'  => 'Sempronius',
                'email' => 'sempronius@example.com',
            ],
            $this->values($fieldCollection),
        );
    }

    public function testSetValuesFromRequestMergesQueryAndInputWithInputTakingPrecedence(): void
    {
        $fieldCollection = new FieldCollection([
            'name'  => $this->createField('name', ['type' => 'text']),
            'email' => $this->createField('email', ['type' => 'email']),
        ]);

        $request = $this->createRequest(
            RequestMethod::POST,
            input: [
                'name' => 'input',
            ],
            query: [
                'name'  => 'query',
                'email' => 'query@example.com',
            ]
        );

        $fieldCollection->setValuesFromRequest($request);

        $this->assertSame(
            [
                'name'  => 'input',
                'email' => 'query@example.com',
            ],
            $this->values($fieldCollection),
        );
    }

    public function testValidateReturnsSameCollection(): void
    {
        $fieldCollection = new FieldCollection([
            'name' => $this->createField('name', [
                'type'  => 'text',
                'value' => 'Sempronius',
            ]),
        ]);

        $this->assertSame($fieldCollection, $fieldCollection->validate());

        $this->assertTrue($fieldCollection->isValidated());
    }

    public function testValidateValidatesAllFields(): void
    {
        $validated = [];

        $first = $this->createField('first', [
            'type'  => 'text',
            'value' => 'first',
        ]);

        $first->setMethods([
            'validate' => function (Field $field, mixed $value) use (&$validated): mixed {
                $validated[] = $field->name();
                return $value;
            },
        ]);

        $second = $this->createField('second', [
            'type'  => 'text',
            'value' => 'second',
        ]);

        $second->setMethods([
            'validate' => function (Field $field, mixed $value) use (&$validated): mixed {
                $validated[] = $field->name();

                return $value;
            },
        ]);

        $fieldCollection = new FieldCollection([
            'first'  => $first,
            'second' => $second,
        ]);

        $fieldCollection->validate();

        $this->assertSame(['first', 'second'], $validated);
        $this->assertTrue($fieldCollection->isValidated());
    }

    public function testIsValidReturnsTrueWhenAllFieldsAreValid(): void
    {
        $fieldCollection = new FieldCollection([
            'name' => $this->createField('name', [
                'type'  => 'text',
                'value' => 'Sempronius',
            ]),
            'email' => $this->createField('email', [
                'type'  => 'text',
                'value' => 'sempronius@example.com',
            ]),
        ]);

        $this->assertTrue($fieldCollection->isValid());
        $this->assertTrue($fieldCollection->isValidated());
    }

    public function testIsValidReturnsFalseWhenOneFieldIsInvalid(): void
    {
        $field = $this->createField('invalid', [
            'type'  => 'text',
            'value' => '',
        ]);

        $field->setMethods([
            'validate' => static function (): never {
                throw new ValidationException('Invalid value');
            },
        ]);

        $valid = $this->createField('valid', [
            'type'  => 'text',
            'value' => 'valid',
        ]);

        $fieldCollection = new FieldCollection([
            'invalid' => $field,
            'valid'   => $valid,
        ]);

        $this->assertFalse($fieldCollection->isValid());
        $this->assertTrue($field->isValidated());
        $this->assertTrue($valid->isValidated());
    }

    public function testIsValidValidatesEveryFieldEvenAfterAnInvalidField(): void
    {
        $validated = [];

        $invalid = $this->createField('invalid', [
            'type'  => 'text',
            'value' => '',
        ]);

        $invalid->setMethods([
            'validate' => function (Field $field, mixed $value) use (&$validated): never {
                $validated[] = $field->name();

                throw new ValidationException('Invalid value');
            },
        ]);

        $valid = $this->createField('valid', [
            'type'  => 'text',
            'value' => 'valid',
        ]);

        $valid->setMethods([
            'validate' => function (Field $field, mixed $value) use (&$validated): mixed {
                $validated[] = $field->name();

                return $value;
            },
        ]);

        $fieldCollection = new FieldCollection([
            'invalid' => $invalid,
            'valid'   => $valid,
        ]);

        $this->assertFalse($fieldCollection->isValid());
        $this->assertSame(['invalid', 'valid'], $validated);
    }

    public function testIsValidatedReturnsFalseUntilAllFieldsHaveBeenValidated(): void
    {
        $fieldCollection = new FieldCollection([
            'first' => $this->createField('first', [
                'type'  => 'text',
                'value' => 'first',
            ]),
            'second' => $this->createField('second', [
                'type'  => 'text',
                'value' => 'second',
            ]),
        ]);

        $this->assertFalse($fieldCollection->isValidated());

        $fieldCollection->first()?->validate();

        $this->assertFalse($fieldCollection->isValidated());

        $fieldCollection->last()?->validate();

        $this->assertTrue($fieldCollection->isValidated());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createField(string $name, array $data): Field
    {
        return new Field($name, $data);
    }

    /**
     * @param array<string, array<string, mixed>> $files
     * @param array<string, string>               $input
     * @param array<string, string>               $query
     */
    private function createRequest(RequestMethod $requestMethod, array $input = [], array $query = [], array $files = []): Request
    {
        return new Request($input, $query, [], $files, ['REQUEST_METHOD' => $requestMethod->value]);
    }

    /**
     * @return array<string, mixed>
     */
    private function values(FieldCollection $fieldCollection, string $key = 'value', mixed $default = null): array
    {
        /** @var array<string, mixed> $values */
        $values = $fieldCollection->extract($key, $default);

        return $values;
    }
}
