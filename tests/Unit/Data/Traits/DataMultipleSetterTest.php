<?php

namespace Formwork\Tests\Unit\Data\Traits;

use Formwork\Data\Traits\DataMultipleSetter;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Data\Fixtures\DataMultipleSetterFixture;
use PHPUnit\Framework\Attributes\CoversTrait;

#[CoversTrait(DataMultipleSetter::class)]
class DataMultipleSetterTest extends TestCase
{
    public function testSetMultiple(): void
    {
        $dataMultipleSetterFixture = new DataMultipleSetterFixture(['key1' => 'value1', 'key2' => 'value2']);

        $dataMultipleSetterFixture->setMultiple(['key1' => 'newValue1', 'key3' => 'value3']);

        $data = $dataMultipleSetterFixture->data();

        $this->assertSame('newValue1', $data['key1']);
        $this->assertSame('value2', $data['key2']);
        $this->assertSame('value3', $data['key3']);
    }

    public function testRemoveMultiple(): void
    {
        $dataMultipleSetterFixture = new DataMultipleSetterFixture(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3']);

        $dataMultipleSetterFixture->removeMultiple(['key1', 'key3']);

        $data = $dataMultipleSetterFixture->data();

        $this->assertArrayNotHasKey('key1', $data);
        $this->assertSame('value2', $data['key2']);
        $this->assertArrayNotHasKey('key3', $data);
    }
}
