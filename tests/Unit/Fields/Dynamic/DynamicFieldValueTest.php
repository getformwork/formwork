<?php

namespace Formwork\Tests\Unit\Fields\Dynamic;

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

    /**
     * @var array<string, mixed>
     */
    private array $originalVars = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalLoader = (new ReflectionProperty(DynamicFieldValue::class, 'varsLoader'))->isInitialized() ? DynamicFieldValue::$varsLoader : null;
        $this->originalVars = $this->vars();
        $this->setVars([]);
    }

    protected function tearDown(): void
    {
        if ($this->originalLoader !== null) {
            DynamicFieldValue::$varsLoader = $this->originalLoader;
        }
        $this->setVars($this->originalVars);
        parent::tearDown();
    }

    public function testValueIsComputedLazily(): void
    {
        $calls = 0;
        DynamicFieldValue::$varsLoader = function () use (&$calls): array {
            ++$calls;
            return ['answer' => 42];
        };
        $dynamic = new DynamicFieldValue('value', 'answer', new Field('f', []));

        $this->assertFalse($dynamic->isComputed());
        $this->assertSame(0, $calls);

        $this->assertSame(42, $dynamic->value());
        $this->assertTrue($dynamic->isComputed());
        $this->assertSame(1, $calls);
    }

    public function testKeyAndFieldAreExposed(): void
    {
        $field = new Field('f', []);
        $dynamic = new DynamicFieldValue('label', 'x', $field);

        $this->assertSame('label', $dynamic->key());
        $this->assertSame($field, $dynamic->field());
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
        $dynamic = new DynamicFieldValue('value', 'counter.next()', new Field('f', []));

        $this->assertSame(1, $dynamic->value());
        $this->assertSame(1, $dynamic->value());
        $dynamic->compute();
        $this->assertSame(1, $dynamic->value());
        $this->assertSame(1, $counter->reads);
    }

    public function testVarsAreLoadedOnceAndSharedBetweenInstances(): void
    {
        $calls = 0;
        DynamicFieldValue::$varsLoader = function () use (&$calls): array {
            ++$calls;
            return ['x' => 'shared'];
        };

        $first = new DynamicFieldValue('value', 'x', new Field('a', []));
        $second = new DynamicFieldValue('value', 'x', new Field('b', []));

        $this->assertSame('shared', $first->value());
        $this->assertSame('shared', $second->value());
        $this->assertSame(1, $calls);
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
        $collection = new FieldCollection();
        $collection->setModel($model);
        $field = new Field('f', [], $collection);

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
        $dynamic = new DynamicFieldValue('label', 'this.label', $field);
        $field->set('label', $dynamic);

        $this->expectException(RecursionException::class);

        $dynamic->value();
    }

    public function testFailedComputationCanBeReportedAgainInTheSameWay(): void
    {
        $this->useVars(['x' => 1]);
        $dynamic = new DynamicFieldValue('value', 'x.unknownMethod()', new Field('f', []));

        $exceptions = [];
        for ($i = 0; $i < 2; ++$i) {
            try {
                $dynamic->value();
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
        $dynamic = new DynamicFieldValue('value', 'x', $field);

        $copy = DynamicFieldValue::withComputed('forced', $dynamic);

        $this->assertNotSame($dynamic, $copy);
        $this->assertTrue($copy->isComputed());
        $this->assertSame('forced', $copy->value());
        $this->assertFalse($dynamic->isComputed());
        $this->assertSame('original', $dynamic->value());
        $this->assertSame($dynamic->key(), $copy->key());
        $this->assertSame($field, $copy->field());
    }

    #[RunInSeparateProcess]
    public function testComputingWithoutALoaderIsRejected(): void
    {
        $this->setVars([]);
        if ((new ReflectionProperty(DynamicFieldValue::class, 'varsLoader'))->isInitialized()) {
            $this->markTestSkipped('The application already registered a vars loader.');
        }

        $dynamic = new DynamicFieldValue('value', 'x', new Field('f', []));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('must be set to a valid Closure');

        $dynamic->value();
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function useVars(array $vars): void
    {
        DynamicFieldValue::$varsLoader = static fn(): array => $vars;
    }

    /**
     * @return array<string, mixed>
     */
    private function vars(): array
    {
        return (new ReflectionProperty(DynamicFieldValue::class, 'vars'))->getValue();
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function setVars(array $vars): void
    {
        (new ReflectionProperty(DynamicFieldValue::class, 'vars'))->setValue(null, $vars);
    }
}
