<?php

namespace Formwork\Tests\Unit\Fields\Dynamic;

use ArrayObject;
use Closure;
use Formwork\Data\Attributes\Getter;
use Formwork\Exceptions\RecursionException;
use Formwork\Fields\Dynamic\DynamicFieldValue;
use Formwork\Fields\Field;
use Formwork\Fields\FieldCollection;
use Formwork\Interpolator\Exceptions\InterpolationException;
use Formwork\Model\Model;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionProperty;
use Throwable;
use UnexpectedValueException;

#[CoversClass(DynamicFieldValue::class)]
final class DynamicFieldValueTest extends TestCase
{
    private ?Closure $originalLoader = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalLoader = (new ReflectionProperty(DynamicFieldValue::class, 'varsLoader'))->isInitialized() ? DynamicFieldValue::$varsLoader : null;
    }

    protected function tearDown(): void
    {
        if ($this->originalLoader !== null) {
            DynamicFieldValue::$varsLoader = $this->originalLoader;
        }
        parent::tearDown();
    }

    public function testValueIsComputedLazily(): void
    {
        $arrayObject = new ArrayObject();
        DynamicFieldValue::$varsLoader = function () use ($arrayObject): array {
            $arrayObject[] = true;
            return ['answer' => 42];
        };
        $dynamicFieldValue = new DynamicFieldValue('value', 'answer', new Field('f', []));

        $this->assertFalse($dynamicFieldValue->isComputed());
        $this->assertCount(0, $arrayObject);

        $this->assertSame(42, $dynamicFieldValue->value());
        $this->assertTrue($dynamicFieldValue->isComputed());
        $this->assertCount(1, $arrayObject);
    }

    public function testKeyAndFieldAreExposed(): void
    {
        $field = new Field('f', []);
        $dynamicFieldValue = new DynamicFieldValue('label', 'x', $field);

        $this->assertSame('label', $dynamicFieldValue->key());
        $this->assertSame($field, $dynamicFieldValue->field());
    }

    public function testComputedValueIsCachedAcrossCalls(): void
    {
        $counter = new class {
            public int $reads = 0;

            public function next(): int
            {
                return ++$this->reads;
            }
        };
        $this->useVars(['counter' => $counter]);
        $dynamicFieldValue = new DynamicFieldValue('value', 'counter.next()', new Field('f', []));

        $this->assertSame(1, $dynamicFieldValue->value());
        $this->assertSame(1, $dynamicFieldValue->value());
        $dynamicFieldValue->compute();
        $this->assertSame(1, $dynamicFieldValue->value());
        $this->assertSame(1, $counter->reads);
    }

    public function testVarsAreTakenFromTheCurrentLoader(): void
    {
        $this->useVars(['x' => 'first']);
        $first = new DynamicFieldValue('value', 'x', new Field('a', []));

        $this->assertSame('first', $first->value());

        $this->useVars(['x' => 'second']);
        $second = new DynamicFieldValue('value', 'x', new Field('b', []));

        $this->assertSame('second', $second->value());
        $this->assertSame('first', $first->value());
    }

    public function testThisRefersToTheOwningField(): void
    {
        $this->useVars(['x' => 1]);
        $field = new Field('title', ['label' => 'My label']);

        $this->assertSame('title', (new DynamicFieldValue('value', 'this.name', $field))->value());
    }

    public function testThisCannotBeReplacedByLoadedVars(): void
    {
        $this->useVars(['this' => 'hijacked']);
        $field = new Field('title', []);

        $this->assertSame('title', (new DynamicFieldValue('value', 'this.name', $field))->value());
    }

    public function testModelIsExposedUnderBothItsIdentifierAndModel(): void
    {
        $this->useVars(['x' => 1]);
        $model = new class extends Model {
            protected const string MODEL_IDENTIFIER = 'widget';

            #[Getter]
            public function title(): string
            {
                return 'From model';
            }
        };
        $fieldCollection = new FieldCollection();
        $fieldCollection->setModel($model);
        $field = new Field('f', [], $fieldCollection);

        $this->assertSame('From model', (new DynamicFieldValue('value', 'widget.title', $field))->value());
        $this->assertSame('From model', (new DynamicFieldValue('value', 'model.title', $field))->value());
    }

    public function testModelVariablesAreUndefinedWithoutAModel(): void
    {
        $this->useVars(['x' => 1]);
        $field = new Field('f', [], new FieldCollection());

        $this->expectException(InterpolationException::class);

        (new DynamicFieldValue('value', 'model.title', $field))->value();
    }

    public function testRecursiveComputationIsDetected(): void
    {
        $this->useVars(['x' => 1]);
        $field = new Field('f', []);
        $dynamicFieldValue = new DynamicFieldValue('label', 'this.label', $field);
        $field->set('label', $dynamicFieldValue);

        $this->expectException(RecursionException::class);

        $dynamicFieldValue->value();
    }

    public function testFailedComputationCanBeReportedAgainInTheSameWay(): void
    {
        $this->useVars(['x' => 1]);
        $dynamicFieldValue = new DynamicFieldValue('value', 'x.unknownMethod()', new Field('f', []));

        $exceptions = [];
        for ($i = 0; $i < 2; ++$i) {
            try {
                $dynamicFieldValue->value();
                $this->fail('Computing an invalid expression should fail.');
            } catch (Throwable $exception) {
                $exceptions[] = $exception::class;
            }
        }

        $this->assertNotContains(RecursionException::class, $exceptions, 'A failed computation must not leave the value flagged as being computed.');
        $this->assertSame($exceptions[0], $exceptions[1]);
    }

    public function testWithComputedProducesAnAlreadyComputedCopy(): void
    {
        $this->useVars(['x' => 'original']);
        $field = new Field('f', []);
        $dynamicFieldValue = new DynamicFieldValue('value', 'x', $field);

        $copy = DynamicFieldValue::withComputed('forced', $dynamicFieldValue);

        $this->assertNotSame($dynamicFieldValue, $copy);
        $this->assertTrue($copy->isComputed());
        $this->assertSame('forced', $copy->value());
        $this->assertFalse($dynamicFieldValue->isComputed());
        $this->assertSame('original', $dynamicFieldValue->value());
        $this->assertSame($dynamicFieldValue->key(), $copy->key());
        $this->assertSame($field, $copy->field());
    }

    #[RunInSeparateProcess]
    public function testComputingWithoutALoaderIsRejected(): void
    {
        if ((new ReflectionProperty(DynamicFieldValue::class, 'varsLoader'))->isInitialized()) {
            $this->markTestSkipped('The application already registered a vars loader.');
        }

        $dynamicFieldValue = new DynamicFieldValue('value', 'x', new Field('f', []));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('must be set to a valid Closure');

        $dynamicFieldValue->value();
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function useVars(array $vars): void
    {
        DynamicFieldValue::$varsLoader = static fn(): array => $vars;
    }
}
