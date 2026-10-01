<?php

namespace Formwork\Tests\Unit\Fields;

use Formwork\Cms\App;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Fields\FieldCollection;
use Formwork\Fields\FieldFactory;
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
    private App $app;

    public function setUp(): void
    {
        $this->app = App::instance();
    }

    public function testSetLayoutAndGetLayout(): void
    {
        $fields = new FieldCollection();
        $layout = $this->createStub(Layout::class);

        $fields->setLayout($layout);

        $this->assertSame($layout, $fields->layout());
    }

    public function testSetModelAndGetModel(): void
    {
        $fields = new FieldCollection();
        $model = $this->createStub(Model::class);

        $fields->setModel($model);

        $this->assertSame($model, $fields->model());
    }

    public function testModelIsNullByDefault(): void
    {
        $fields = new FieldCollection();

        $this->assertNull($fields->model());
    }

    public function testExtractReturnsFieldValuesWithKeys(): void
    {
        $fields = new FieldCollection([
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
            $fields->extract('value'),
        );
    }

    public function testExtractUsesDefaultForMissingKeys(): void
    {
        $fields = new FieldCollection([
            'name'  => $this->createField('name', ['type' => 'text']),
            'email' => $this->createField('email', ['type' => 'email']),
        ]);

        $this->assertSame(
            [
                'name'  => 'fallback',
                'email' => 'fallback',
            ],
            $fields->extract('missing', 'fallback'),
        );
    }

    public function testSetValuesReturnsSameCollection(): void
    {
        $fields = new FieldCollection([
            'name' => $this->createField('name', ['type' => 'text']),
        ]);

        $this->assertSame($fields, $fields->setValues(['name' => 'Sempronius']));
    }

    public function testSetValuesSetsValuesForFieldsPresentInData(): void
    {
        $fields = new FieldCollection([
            'name'  => $this->createField('name', ['type' => 'text']),
            'email' => $this->createField('email', ['type' => 'email']),
        ]);

        $fields->setValues([
            'name'  => 'Sempronius',
            'email' => 'sempronius@example.com',
        ]);

        $this->assertSame(
            [
                'name'  => 'Sempronius',
                'email' => 'sempronius@example.com',
            ],
            $fields->extract('value'),
        );
    }

    public function testSetValuesDoesNotChangeFieldsMissingFromData(): void
    {
        $fields = new FieldCollection([
            'name' => $this->createField('name', [
                'type'  => 'text',
                'value' => 'Existing name',
            ]),
            'email' => $this->createField('email', [
                'type'  => 'email',
                'value' => 'existing@example.com',
            ]),
        ]);

        $fields->setValues(['name' => 'Sempronius']);

        $this->assertSame(
            [
                'name'  => 'Sempronius',
                'email' => 'existing@example.com',
            ],
            $fields->extract('value'),
        );
    }

    public function testSetValuesUsesExplicitDefaultForMissingFields(): void
    {
        $fields = new FieldCollection([
            'name' => $this->createField('name', [
                'type'  => 'text',
                'value' => 'Existing name',
            ]),
            'email' => $this->createField('email', [
                'type'  => 'email',
                'value' => 'existing@example.com',
            ]),
        ]);

        $fields->setValues(['name' => 'Sempronius'], null);

        $this->assertSame(
            [
                'name'  => 'Sempronius',
                'email' => null,
            ],
            $fields->extract('value'),
        );
    }

    #[TestWith(['enabled', false], name: "'enabled' => false")]
    #[TestWith(['nullable', null], name: "'nullable' => null")]
    #[TestWith(['empty', ''], name: "'empty' => ''")]
    public function testSetValuesPreservesValuesThatArePresent(string $name, mixed $value): void
    {
        $fields = new FieldCollection([
            $name => $this->createField($name, [
                'type'  => 'text',
                'value' => 'original',
            ]),
        ]);

        $fields->setValues([$name => $value]);

        $this->assertSame([$name => $value], $fields->extract('value'));
    }

    public function testSetValuesCanReplaceAnExistingValueWithNull(): void
    {
        $fields = new FieldCollection([
            'value' => $this->createField('value', [
                'type'  => 'text',
                'value' => 'original',
            ]),
        ]);

        $fields->setValues(['value' => null]);

        $this->assertSame(['value' => null], $fields->extract('value'));
    }

    public function testSetValuesFromRequestSetsRequestValues(): void
    {
        $fields = new FieldCollection([
            'name'  => $this->createField('name', ['type' => 'text']),
            'email' => $this->createField('email', ['type' => 'email']),
        ]);

        $request = $this->createRequest(RequestMethod::POST, input: [
            'name'  => 'Sempronius',
            'email' => 'sempronius@example.com',
        ]);

        $this->assertSame($fields, $fields->setValuesFromRequest($request));

        $this->assertSame(
            [
                'name'  => 'Sempronius',
                'email' => 'sempronius@example.com',
            ],
            $fields->extract('value'),
        );
    }

    public function testSetValuesFromRequestMergesQueryAndInputWithInputTakingPrecedence(): void
    {
        $fields = new FieldCollection([
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

        $fields->setValuesFromRequest($request);

        $this->assertSame(
            [
                'name'  => 'input',
                'email' => 'query@example.com',
            ],
            $fields->extract('value'),
        );
    }

    public function testValidateReturnsSameCollection(): void
    {
        $fields = new FieldCollection([
            'name' => $this->createField('name', [
                'type'  => 'text',
                'value' => 'Sempronius',
            ]),
        ]);

        $this->assertSame($fields, $fields->validate());

        $this->assertTrue($fields->isValidated());
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

        $fields = new FieldCollection([
            'first'  => $first,
            'second' => $second,
        ]);

        $fields->validate();

        $this->assertSame(['first', 'second'], $validated);
        $this->assertTrue($fields->isValidated());
    }

    public function testIsValidReturnsTrueWhenAllFieldsAreValid(): void
    {
        $fields = new FieldCollection([
            'name' => $this->createField('name', [
                'type'  => 'text',
                'value' => 'Sempronius',
            ]),
            'email' => $this->createField('email', [
                'type'  => 'text',
                'value' => 'sempronius@example.com',
            ]),
        ]);

        $this->assertTrue($fields->isValid());
        $this->assertTrue($fields->isValidated());
    }

    public function testIsValidReturnsFalseWhenOneFieldIsInvalid(): void
    {
        $invalid = $this->createField('invalid', [
            'type'  => 'text',
            'value' => '',
        ]);

        $invalid->setMethods([
            'validate' => static function (): never {
                throw new ValidationException('Invalid value');
            },
        ]);

        $valid = $this->createField('valid', [
            'type'  => 'text',
            'value' => 'valid',
        ]);

        $fields = new FieldCollection([
            'invalid' => $invalid,
            'valid'   => $valid,
        ]);

        $this->assertFalse($fields->isValid());
        $this->assertTrue($invalid->isValidated());
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

        $fields = new FieldCollection([
            'invalid' => $invalid,
            'valid'   => $valid,
        ]);

        $this->assertFalse($fields->isValid());
        $this->assertSame(['invalid', 'valid'], $validated);
    }

    public function testIsValidatedReturnsFalseUntilAllFieldsHaveBeenValidated(): void
    {
        $fields = new FieldCollection([
            'first' => $this->createField('first', [
                'type'  => 'text',
                'value' => 'first',
            ]),
            'second' => $this->createField('second', [
                'type'  => 'text',
                'value' => 'second',
            ]),
        ]);

        $this->assertFalse($fields->isValidated());

        $fields->first()->validate();

        $this->assertFalse($fields->isValidated());

        $fields->last()->validate();

        $this->assertTrue($fields->isValidated());
    }

    private function createField(string $name, array $data): Field
    {
        return $this->app->getService(FieldFactory::class)->make($name, $data);
    }

    private function createRequest(RequestMethod $method, array $input = [], array $query = [], array $files = []): Request
    {
        return new Request($input, $query, [], $files, ['REQUEST_METHOD' => $method->value]);
    }
}
