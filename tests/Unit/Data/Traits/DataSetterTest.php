<?php

namespace Formwork\Tests\Unit\Data\Traits;

use Formwork\Data\Traits\DataSetter;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Data\Fixtures\DataSetterFixture;
use PHPUnit\Framework\Attributes\CoversTrait;

#[CoversTrait(DataSetter::class)]
final class DataSetterTest extends TestCase
{
    public function testSet(): void
    {
        $dataSetterFixture = new DataSetterFixture();

        $dataSetterFixture->set('key1', 'value1');
        $this->assertSame(['key1' => 'value1'], $dataSetterFixture->data());

        $dataSetterFixture->set('key2', 'value2');
        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $dataSetterFixture->data());

        $dataSetterFixture->set('key1', 'newValue1');
        $this->assertSame(['key1' => 'newValue1', 'key2' => 'value2'], $dataSetterFixture->data());
    }

    public function testRemove(): void
    {
        $dataSetterFixture = new DataSetterFixture(['key1' => 'value1', 'key2' => 'value2']);

        $dataSetterFixture->remove('key1');
        $this->assertSame(['key2' => 'value2'], $dataSetterFixture->data());

        $dataSetterFixture->remove('key3'); // Removing non-existent key
        $this->assertSame(['key2' => 'value2'], $dataSetterFixture->data());

        $dataSetterFixture->remove('key2');
        $this->assertSame([], $dataSetterFixture->data());
    }
}
