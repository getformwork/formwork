<?php

namespace Formwork\Tests\Data\Traits;

use Formwork\Data\Traits\DataSetter;
use Formwork\Tests\Data\Fixtures\DataSetterFixture;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

#[CoversTrait(DataSetter::class)]
final class DataSetterTest extends TestCase
{
    public function testSet(): void
    {
        $fixture = new DataSetterFixture();

        $fixture->set('key1', 'value1');
        $this->assertSame(['key1' => 'value1'], $fixture->data());

        $fixture->set('key2', 'value2');
        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $fixture->data());

        $fixture->set('key1', 'newValue1');
        $this->assertSame(['key1' => 'newValue1', 'key2' => 'value2'], $fixture->data());
    }

    public function testRemove(): void
    {
        $fixture = new DataSetterFixture(['key1' => 'value1', 'key2' => 'value2']);

        $fixture->remove('key1');
        $this->assertSame(['key2' => 'value2'], $fixture->data());

        $fixture->remove('key3'); // Removing non-existent key
        $this->assertSame(['key2' => 'value2'], $fixture->data());

        $fixture->remove('key2');
        $this->assertSame([], $fixture->data());
    }
}
