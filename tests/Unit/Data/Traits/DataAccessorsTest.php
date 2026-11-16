<?php

namespace Formwork\Tests\Unit\Data\Traits;

use Formwork\Data\Traits\DataAccessors;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Data\Fixtures\DataAccessorsFixture;
use Formwork\Tests\Unit\Data\Fixtures\DataDuplicateGettersFixture;
use Formwork\Tests\Unit\Data\Fixtures\DataDuplicateSettersFixture;
use LogicException;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversTrait(DataAccessors::class)]
final class DataAccessorsTest extends TestCase
{
    #[DataProvider('getterKeyProvider')]
    public function testGetterKeysAreResolvedFromTheirNames(string $key, mixed $expected): void
    {
        $dataAccessorsFixture = new DataAccessorsFixture();

        $this->assertTrue($dataAccessorsFixture->has($key));
        $this->assertSame($expected, $dataAccessorsFixture->get($key));
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function getterKeyProvider(): iterable
    {
        yield 'property' => ['secret', 'Secret'];
        yield 'property with a given key' => ['label', 'Name'];
        yield 'method with the get prefix' => ['slug', 'slug'];
        yield 'method with the is prefix' => ['published', true];
        yield 'method starting like a prefix' => ['issue', 'issue'];
    }

    #[DataProvider('setterKeyProvider')]
    public function testSetterKeysAreResolvedFromTheirNames(string $key, string $expected): void
    {
        $dataAccessorsFixture = new DataAccessorsFixture();

        $dataAccessorsFixture->set($key, 'value');

        $this->assertSame([$key => $expected], $dataAccessorsFixture->data());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function setterKeyProvider(): iterable
    {
        yield 'method with the set prefix' => ['color', 'VALUE'];
        yield 'method without the set prefix' => ['tone', 'value'];
        yield 'method with a given key' => ['size', 'valuepx'];
    }

    public function testKeysCannotHaveMoreThanOneGetter(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Multiple getters registered for data key "slug".');
        (new DataDuplicateGettersFixture())->get('slug');
    }

    public function testKeysCannotHaveMoreThanOneSetter(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Multiple setters registered for data key "slug".');
        (new DataDuplicateSettersFixture())->set('slug', 'other');
    }
}
