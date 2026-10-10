<?php

namespace Formwork\Tests\Unit\Model;

use BadMethodCallException;
use Formwork\Cms\App;
use Formwork\Fields\Field;
use Formwork\Fields\FieldCollection;
use Formwork\Model\Model;
use Formwork\Schemes\Scheme;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Model\Fixtures\ModelFixture;
use Formwork\Tests\Unit\Model\Fixtures\UninitializedModelFixture;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Model::class)]
final class ModelTest extends TestCase
{
    public function testModelIdentifier(): void
    {
        $this->assertSame('fixture', $this->model()->getModelIdentifier());
    }

    public function testSchemeIsReturned(): void
    {
        $scheme = $this->scheme();

        $this->assertSame($scheme, (new ModelFixture([], $scheme))->scheme());
    }

    public function testFieldsAreCreatedLazilyAndCached(): void
    {
        $scheme = $this->createMock(Scheme::class);
        $scheme->expects($this->once())->method('fields')->willReturn(new FieldCollection());
        $model = new ModelFixture([], $scheme);

        $this->assertSame($model->fields(), $model->fields());
    }

    public function testFieldsKnowTheirModelAndReceiveTheData(): void
    {
        $model = $this->model(['title' => 'Hello']);

        $this->assertSame($model, $model->fields()->model());
        $this->assertSame('Hello', $model->fields()->get('title')->value());
    }

    public function testDataIsReturned(): void
    {
        $this->assertSame(['title' => 'Hello'], $this->model(['title' => 'Hello'])->data());
    }

    public function testAppReturnsTheApplicationInstance(): void
    {
        $this->assertInstanceOf(App::class, $this->model()->get('app'));
    }

    // has()

    public function testHasReportsAttributedGettersEvenWhenTheyReturnNothing(): void
    {
        $model = $this->model();

        $this->assertTrue($model->has('summary'));
        $this->assertTrue($model->has('alias'));
    }

    public function testHasReportsFields(): void
    {
        $this->assertTrue($this->model()->has('title'));
    }

    public function testHasReportsDataKeysIncludingNestedOnes(): void
    {
        $model = $this->model(['extra' => ['nested' => ['value' => 1]]]);

        $this->assertTrue($model->has('extra'));
        $this->assertTrue($model->has('extra.nested.value'));
        $this->assertFalse($model->has('extra.nested.missing'));
    }

    public function testHasIsFalseForUnknownKeys(): void
    {
        $this->assertFalse($this->model()->has('nothing'));
    }

    public function testHasDeprecatesImplicitPropertyChecks(): void
    {
        $model = $this->model();
        $result = null;

        $messages = $this->captureDeprecations(function () use ($model, &$result): void {
            $result = $model->has('legacy');
        });

        $this->assertTrue($result);
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('ModelFixture::$legacy', $messages[0]);
    }

    // get()

    public function testGetUsesMethodGetters(): void
    {
        $this->assertSame('Summary of Hello', $this->model(['title' => 'Hello'])->get('summary'));
    }

    public function testGetUsesPropertyGettersAndCustomKeys(): void
    {
        $model = $this->model();

        $this->assertSame('initial-slug', $model->get('slug'));
        $this->assertSame('secret-alias', $model->get('alias'));
    }

    public function testGetterKeyIsDerivedFromTheMethodName(): void
    {
        $this->assertSame('default-mode', $this->model()->get('mode'));
    }

    public function testUninitializedPropertyGetterReturnsTheDefault(): void
    {
        $model = new UninitializedModelFixture([], $this->scheme());

        $this->assertTrue($model->has('uninitialized'));
        $this->assertSame('fallback', $model->get('uninitialized', 'fallback'));
    }

    public function testToArraySurvivesUninitializedPropertyGetters(): void
    {
        $model = new UninitializedModelFixture(['title' => 'Hello'], $this->scheme());

        $array = $this->exportedWithoutDeprecations($model);

        $this->assertSame('Hello', $array['title']);
    }

    public function testGetReadsFieldValues(): void
    {
        $this->assertSame('Hello', $this->model(['title' => 'Hello'])->get('title'));
    }

    public function testGetPrefersTheReturnMethodOfAFieldToItsValue(): void
    {
        $scheme = $this->scheme(function (): FieldCollection {
            $field = new Field('title', ['type' => 'text', 'value' => 'raw']);
            $field->setMethods(['return' => static fn(Field $field): string => 'returned:' . $field->value()]);
            return new FieldCollection(['title' => $field]);
        });

        $this->assertSame('returned:raw', (new ModelFixture(['title' => 'raw'], $scheme))->get('title'));
    }

    public function testGetFallsBackToTheDataAndSupportsNestedKeys(): void
    {
        $model = $this->model(['extra' => ['nested' => 'deep']]);

        $this->assertSame(['nested' => 'deep'], $model->get('extra'));
        $this->assertSame('deep', $model->get('extra.nested'));
    }

    public function testGetReturnsTheDefaultForMissingKeys(): void
    {
        $model = $this->model();

        $this->assertNull($model->get('nothing'));
        $this->assertSame('default', $model->get('nothing', 'default'));
        $this->assertSame('default', $model->get('missing.nested.key', 'default'));
    }

    public function testGetDeprecatesImplicitPropertyAccess(): void
    {
        $model = $this->model();
        $result = null;

        $messages = $this->captureDeprecations(function () use ($model, &$result): void {
            $result = $model->get('locked');
        });

        $this->assertSame('locked-value', $result);
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('implicitly with the get() method is deprecated', $messages[0]);
    }

    public function testGetDeprecatesImplicitGetterMethodsAndUsesThem(): void
    {
        $model = $this->model();
        $result = null;

        $messages = $this->captureDeprecations(function () use ($model, &$result): void {
            $result = $model->get('legacy');
        });

        $this->assertSame('legacy-from-method', $result);
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('implicit getter method', $messages[0]);
    }

    public function testGetDoesNotExposeInternalStateAsData(): void
    {
        $model = $this->model(['title' => 'Hello']);
        $result = null;

        $this->captureDeprecations(function () use ($model, &$result): void {
            $result = $model->get('dataAccessors', 'default');
        });

        $this->assertSame('default', $result, 'The accessor cache must not leak through get().');
    }

    // set()

    public function testSetUsesMethodSetters(): void
    {
        $model = $this->model();

        $model->set('label', 'hello');

        $this->assertSame('HELLO', $model->get('label'));
    }

    public function testSetterCanBeRegisteredUnderACustomKey(): void
    {
        $model = $this->model();

        $model->set('mode', 'dark');

        $this->assertSame('dark', $model->get('mode'));
    }

    public function testSetUsesPropertySetters(): void
    {
        $model = $this->model();

        $model->set('slug', 'new-slug');

        $this->assertSame('new-slug', $model->get('slug'));
    }

    public function testSetRefusesGetterOnlyKeys(): void
    {
        $model = $this->model();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot set getter-only key "summary"');

        $model->set('summary', 'overridden');
    }

    public function testSetRefusesGetterOnlyPropertyKeysUnderTheirCustomName(): void
    {
        $model = $this->model();

        $this->expectException(LogicException::class);

        $model->set('alias', 'overridden');
    }

    public function testSetRefusesReadonlyProperties(): void
    {
        $model = $this->model();

        $this->captureDeprecations(function () use ($model): void {
            try {
                $model->set('locked', 'changed');
                $this->fail('A readonly model property cannot be set.');
            } catch (BadMethodCallException $exception) {
                $this->assertStringContainsString('$locked', $exception->getMessage());
            }
        });

        $this->assertSame('locked-value', $model->locked);
    }

    public function testSetDeprecatesImplicitSetterMethodsAndUsesThem(): void
    {
        $model = $this->model();

        $messages = $this->captureDeprecations(fn() => $model->set('shout', 'loud'));

        $this->assertSame('LOUD', $model->shout);
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('implicit setter method', $messages[0]);
    }

    public function testSetDeprecatesImplicitPropertyAssignment(): void
    {
        $model = $this->model();

        $messages = $this->captureDeprecations(fn() => $model->set('legacy', 'changed'));

        $this->assertSame('changed', $model->legacy);
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('implicitly with the set() method is deprecated', $messages[0]);
    }

    public function testSetStoresUnknownKeysInTheData(): void
    {
        $model = $this->model();

        $model->set('custom', 'value');

        $this->assertSame('value', $model->get('custom'));
        $this->assertSame(['custom' => 'value'], $model->data());
    }

    public function testSetSupportsNestedKeys(): void
    {
        $model = $this->model(['extra' => ['keep' => 1]]);

        $model->set('extra.added', 2);

        $this->assertSame(['keep' => 1, 'added' => 2], $model->get('extra'));
    }

    public function testSetUpdatesTheFieldOnceFieldsAreLoaded(): void
    {
        $model = $this->model(['title' => 'Old']);
        $model->fields();

        $model->set('title', 'New');

        $this->assertSame('New', $model->get('title'));
        $this->assertSame('New', $model->fields()->get('title')->value());
        $this->assertSame('New', $model->data()['title']);
    }

    public function testSetStoresTheValueNormalizedByFieldValidation(): void
    {
        $model = $this->trimmingModel();
        $model->fields();

        $model->set('title', '  padded  ');

        $this->assertSame('padded', $model->data()['title']);
    }

    public function testSetNormalizesValuesEvenIfFieldsWereNeverLoaded(): void
    {
        $model = $this->trimmingModel();

        $model->set('title', '  padded  ');

        $this->assertSame('padded', $model->data()['title'], 'Stored data should not depend on whether fields were loaded before.');
    }

    public function testSetThroughAFieldKeyIsVisibleToSubsequentGets(): void
    {
        $model = $this->model(['title' => 'Old']);

        $this->assertSame('Old', $model->get('title'));
        $model->set('title', 'New');

        $this->assertSame('New', $model->get('title'));
    }

    public function testSetMultipleAndGetMultiple(): void
    {
        $model = $this->model();

        $model->setMultiple(['custom' => 1, 'other' => 2, 'slug' => 'multi']);

        $this->assertSame(['custom' => 1, 'other' => 2, 'slug' => 'multi'], $model->getMultiple(['custom', 'other', 'slug']));
    }

    // __call()

    public function testCallReturnsGettersFieldsAndData(): void
    {
        $model = $this->model(['title' => 'Hello', 'extra' => 'x']);

        $this->assertSame('Summary of Hello', $model->summary());
        $this->assertSame('Hello', $model->title());
        $this->assertSame('x', $model->extra());
    }

    public function testCallFailsOnUnknownMethods(): void
    {
        $model = $this->model();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Call to undefined method ' . ModelFixture::class . '::nothing()');

        $model->nothing();
    }

    // toArray()

    public function testToArrayMergesDataAndExportedGettersSortedByKey(): void
    {
        $model = $this->model(['zeta' => 1, 'alpha' => 2]);

        $array = $this->exportedWithoutDeprecations($model);

        $keys = array_keys($array);
        $sorted = $keys;
        sort($sorted);

        $this->assertSame($sorted, $keys);
        $this->assertSame(1, $array['zeta']);
        $this->assertSame(2, $array['alpha']);
        $this->assertSame('initial-slug', $array['slug']);
        $this->assertSame('Summary of nothing', $array['summary']);
    }

    public function testToArrayOmitsNonExportedGetters(): void
    {
        $array = $this->exportedWithoutDeprecations($this->model());

        $this->assertArrayNotHasKey('hidden', $array);
        $this->assertArrayNotHasKey('alias', $array);
        $this->assertArrayNotHasKey('data', $array);
    }

    public function testToArrayDropsDataThatShadowsANonExportedGetter(): void
    {
        $array = $this->exportedWithoutDeprecations($this->model(['hidden' => 'from-data']));

        $this->assertArrayNotHasKey('hidden', $array);
    }

    public function testToArrayGettersTakePrecedenceOverData(): void
    {
        $array = $this->exportedWithoutDeprecations($this->model(['slug' => 'from-data']));

        $this->assertSame('initial-slug', $array['slug']);
    }

    public function testToArrayDeprecatesImplicitlyExportedProperties(): void
    {
        $model = $this->model();
        $array = [];

        $messages = $this->captureDeprecations(function () use ($model, &$array): void {
            $array = $model->toArray();
        });

        $this->assertStringContainsString('toArray() method is deprecated', $messages[0]);
        $this->assertStringContainsString('ModelFixture::$legacy', $messages[0]);
        $this->assertStringNotContainsString('dataAccessors', $messages[0]);
        $this->assertStringNotContainsString('::$data,', $messages[0]);
        $this->assertSame('legacy-from-method', $array['legacy'], 'The implicit getter method wins over the property.');
    }

    public function testToArrayDoesNotExportTheApplicationInstance(): void
    {
        $array = $this->exportedWithoutDeprecations($this->model());

        $this->assertArrayNotHasKey('app', $array, 'Exporting a model should never carry the whole application along.');
    }

    // Helpers

    /**
     * @return array<string, mixed>
     */
    private function exportedWithoutDeprecations(Model $model): array
    {
        $array = [];
        $this->captureDeprecations(function () use ($model, &$array): void {
            $array = $model->toArray();
        });
        return $array;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function model(array $data = []): ModelFixture
    {
        return new ModelFixture($data, $this->scheme());
    }

    private function trimmingModel(): ModelFixture
    {
        return new ModelFixture([], $this->scheme(function (): FieldCollection {
            $field = new Field('title', ['type' => 'text']);
            $field->setMethods(['validate' => static fn(Field $field, mixed $value): string => trim((string) $value)]);
            return new FieldCollection(['title' => $field]);
        }));
    }

    /**
     * @param (callable(): FieldCollection)|null $fields
     */
    private function scheme(?callable $fields = null): Scheme
    {
        $fields ??= static fn(): FieldCollection => new FieldCollection([
            'title' => new Field('title', ['type' => 'text']),
        ]);

        $scheme = $this->createStub(Scheme::class);
        $scheme->method('fields')->willReturnCallback($fields);
        return $scheme;
    }
}
