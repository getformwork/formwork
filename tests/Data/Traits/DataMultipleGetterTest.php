<?php

namespace Formwork\Tests\Data\Traits;

use Formwork\Data\Traits\DataMultipleGetter;
use Formwork\Tests\Data\Fixtures\DataMultipleGetterFixture;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

#[CoversTrait(DataMultipleGetter::class)]
class DataMultipleGetterTest extends TestCase
{
    public function testGetMultipleReturnsCorrectValues(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'];
        $fixture = new DataMultipleGetterFixture($data);
        $values = $fixture->getMultiple(['key1', 'key3']);
        $this->assertSame(['key1' => 'value1', 'key3' => 'value3'], $values);
    }

    public function testGetMultipleWithNonExistentKeysReturnsDefault(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $fixture = new DataMultipleGetterFixture($data);
        $values = $fixture->getMultiple(['key1', 'key3'], 'default');
        $this->assertSame(['key1' => 'value1', 'key3' => 'default'], $values);
    }

    public function testHasMultipleReturnsTrueWhenAllKeysExist(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $fixture = new DataMultipleGetterFixture($data);
        $this->assertTrue($fixture->hasMultiple(['key1', 'key2']));
    }

    public function testHasMultipleReturnsFalseWhenAnyKeyDoesNotExist(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $fixture = new DataMultipleGetterFixture($data);
        $this->assertFalse($fixture->hasMultiple(['key1', 'key3']));
    }
}
