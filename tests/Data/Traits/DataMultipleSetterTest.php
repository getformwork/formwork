<?php

namespace Formwork\Tests\Data\Traits;

use Formwork\Data\Traits\DataMultipleSetter;
use Formwork\Tests\Data\Fixtures\DataMultipleSetterFixture;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

#[CoversTrait(DataMultipleSetter::class)]
class DataMultipleSetterTest extends TestCase
{
    public function testSetMultiple(): void
    {
        $fixture = new DataMultipleSetterFixture(['key1' => 'value1', 'key2' => 'value2']);

        $fixture->setMultiple(['key1' => 'newValue1', 'key3' => 'value3']);

        $data = $fixture->data();

        $this->assertSame('newValue1', $data['key1']);
        $this->assertSame('value2', $data['key2']);
        $this->assertSame('value3', $data['key3']);
    }

    public function testRemoveMultiple(): void
    {
        $fixture = new DataMultipleSetterFixture(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3']);

        $fixture->removeMultiple(['key1', 'key3']);

        $data = $fixture->data();

        $this->assertArrayNotHasKey('key1', $data);
        $this->assertSame('value2', $data['key2']);
        $this->assertArrayNotHasKey('key3', $data);
    }
}
