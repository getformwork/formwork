<?php

namespace Formwork\Tests\Unit\Fields;

use Formwork\Cms\App;
use Formwork\Fields\Field;
use Formwork\Fields\FieldFactory;
use Formwork\Schemes\Schemes;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Field::class)]
#[UsesApplication]
final class FieldTypesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // `FieldFactory` is registered lazily by the schemes service loader
        App::instance()->getService(Schemes::class);
    }

    #[DataProvider('stringFieldTypeProvider')]
    public function testFieldsExpectingStringsRejectArrays(string $type): void
    {
        $field = App::instance()->getService(FieldFactory::class)->make('field', ['type' => $type]);

        // Like a value submitted as `field[]=value`
        $field->set('value', ['value']);

        $this->assertFalse($field->isValid());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function stringFieldTypeProvider(): iterable
    {
        yield 'email' => ['email'];
        yield 'color' => ['color'];
        yield 'date' => ['date'];
    }
}
