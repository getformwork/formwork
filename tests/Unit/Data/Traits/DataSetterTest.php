<?php

namespace Formwork\Tests\Unit\Data\Traits;

use Formwork\Data\Traits\DataSetter;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Data\Fixtures\DataAccessorsFixture;
use Formwork\Tests\Unit\Data\Fixtures\DataSetterFixture;
use LogicException;
use PHPUnit\Framework\Attributes\CoversTrait;

#[CoversTrait(DataSetter::class)]
final class DataSetterTest extends TestCase
{
    public function testSetStoresTheValue(): void
    {
        $dataSetterFixture = new DataSetterFixture();

        $dataSetterFixture->set('key1', 'value1');
        $this->assertSame(['key1' => 'value1'], $dataSetterFixture->data());

        $dataSetterFixture->set('key2', 'value2');
        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $dataSetterFixture->data());

        $dataSetterFixture->set('key1', 'newValue1');
        $this->assertSame(['key1' => 'newValue1', 'key2' => 'value2'], $dataSetterFixture->data());
    }

    public function testRemoveDeletesTheKey(): void
    {
        $dataSetterFixture = new DataSetterFixture(['key1' => 'value1', 'key2' => 'value2']);

        $dataSetterFixture->remove('key1');
        $this->assertSame(['key2' => 'value2'], $dataSetterFixture->data());

        $dataSetterFixture->remove('key3'); // Removing non-existent key
        $this->assertSame(['key2' => 'value2'], $dataSetterFixture->data());

        $dataSetterFixture->remove('key2');
        $this->assertSame([], $dataSetterFixture->data());
    }

    public function testSetterPropertiesAreSetInsteadOfTheData(): void
    {
        $dataAccessorsFixture = new DataAccessorsFixture();

        $dataAccessorsFixture->set('title', 'Title');

        $this->assertSame('Title', $dataAccessorsFixture->get('title'));
        $this->assertSame([], $dataAccessorsFixture->data());
    }

    public function testSetterMethodsAreCalledWithTheValue(): void
    {
        $dataAccessorsFixture = new DataAccessorsFixture();

        $dataAccessorsFixture->set('color', 'red');

        $this->assertSame(['color' => 'RED'], $dataAccessorsFixture->data());
    }

    public function testGetterOnlyKeysCannotBeSet(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot set getter-only key slug');
        (new DataAccessorsFixture())->set('slug', 'other');
    }

    public function testAccessorKeysCannotBeRemoved(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot remove getter- or setter-backed key title');
        (new DataAccessorsFixture())->remove('title');
    }
}
