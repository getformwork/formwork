<?php

namespace Formwork\Tests\Unit\Fields;

use Formwork\Exceptions\RecursionException;
use Formwork\Fields\Field;
use Formwork\Fields\FieldCollection;
use Formwork\Fields\FieldFactory;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Schemes\Fixtures\BuildsSchemes;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

#[CoversClass(FieldFactory::class)]
final class FieldFactoryTest extends TestCase
{
    use BuildsSchemes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchemes();
    }

    protected function tearDown(): void
    {
        $this->tearDownSchemes();
        parent::tearDown();
    }

    public function testFieldIsCreatedWithNameAndData(): void
    {
        $field = $this->fieldFactory->make('title', ['type' => 'text', 'label' => 'Title', 'required' => true]);

        $this->assertInstanceOf(Field::class, $field);
        $this->assertSame('title', $field->name());
        $this->assertSame('text', $field->type());
        $this->assertSame('Title', $field->label());
        $this->assertTrue($field->isRequired());
    }

    public function testParentCollectionIsPassedToTheField(): void
    {
        $collection = new FieldCollection();

        $this->assertSame($collection, $this->fieldFactory->make('title', ['type' => 'text'], $collection)->parent());
        $this->assertNull($this->fieldFactory->make('title', ['type' => 'text'])->parent());
    }

    public function testFieldReceivesTheCurrentTranslation(): void
    {
        $this->translations->setCurrent('it');

        $field = $this->fieldFactory->make('title', ['type' => 'text', 'label' => '{{greeting}}']);

        $this->assertSame('Ciao', $field->label());
    }

    public function testDefaultValueComesFromTheTypeConfiguration(): void
    {
        $this->assertSame('Untitled', $this->fieldFactory->make('title', ['type' => 'title'])->value());
    }

    public function testExplicitDefaultWinsOverTheTypeConfiguration(): void
    {
        $this->assertSame('Mine', $this->fieldFactory->make('title', ['type' => 'title', 'default' => 'Mine'])->value());
    }

    public function testExplicitNullDefaultIsKept(): void
    {
        $field = $this->fieldFactory->make('title', ['type' => 'title', 'default' => null]);

        $this->assertNull($field->value());
    }

    public function testMethodsComeFromTheTypeConfiguration(): void
    {
        $field = $this->fieldFactory->make('t', ['type' => 'text', 'value' => 'abc']);

        $this->assertTrue($field->hasMethod('upper'));
        $this->assertSame('ABC', $field->upper());
    }

    public function testMethodsAndDefaultsAreInheritedThroughTheExtensionChain(): void
    {
        $field = $this->fieldFactory->make('t', ['type' => 'headline', 'value' => 'abc']);

        $this->assertTrue($field->hasMethod('shout'), 'Inherited from title');
        $this->assertSame('abc!', $field->shout());
        $this->assertSame('HEADLINE', $field->upper(), 'headline overrides the method defined by text');
    }

    public function testDefaultsAreInheritedThroughTheExtensionChain(): void
    {
        $this->assertSame('Untitled', $this->fieldFactory->make('t', ['type' => 'headline'])->value());
    }

    public function testTypeWithoutConfigurationFileProducesAPlainField(): void
    {
        $field = $this->fieldFactory->make('t', ['type' => 'no-such-type', 'value' => 'x']);

        $this->assertSame('x', $field->value());
        $this->assertFalse($field->hasMethod('upper'));
    }

    public function testExtendingAMissingTypeIsRejected(): void
    {
        FileSystem::write($this->fieldsPath . '/orphan.php', "<?php\nreturn fn() => ['extend' => 'does-not-exist'];\n");

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field type "does-not-exist" does not exist');

        $this->fieldFactory->make('t', ['type' => 'orphan']);
    }

    public function testConfigurationCallbacksReceiveContainerServices(): void
    {
        FileSystem::write($this->fieldsPath . '/injected.php', "<?php\nreturn fn(\\Formwork\\Translations\\Translations \$translations) => ['default' => \$translations->getCurrent()->code()];\n");

        $this->assertSame('en', $this->fieldFactory->make('t', ['type' => 'injected'])->value());
    }

    public function testFieldsCreatedByTheSameFactoryAreIndependent(): void
    {
        $first = $this->fieldFactory->make('a', ['type' => 'text', 'value' => 'one']);
        $second = $this->fieldFactory->make('b', ['type' => 'text']);

        $first->set('value', 'changed');

        $this->assertSame('', $second->value());
    }

    #[RunInSeparateProcess]
    public function testCircularTypeExtensionIsDetectedInsteadOfLoopingForever(): void
    {
        set_time_limit(3);

        $this->expectException(RecursionException::class);

        $this->fieldFactory->make('t', ['type' => 'loop-a']);
    }
}
