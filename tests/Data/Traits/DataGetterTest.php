<?php

namespace Formwork\Tests\Data\Traits;

use Formwork\Data\Traits\DataGetter;
use Formwork\Tests\Data\Fixtures\DataGetterFixture;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversTrait;

#[CoversTrait(DataGetter::class)]
final class DataGetterTest extends TestCase
{
    public function testGetReturnsValueForExistingKey(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $fixture = new DataGetterFixture($data);
        $this->assertSame('value1', $fixture->get('key1'));
        $this->assertSame('value2', $fixture->get('key2'));
    }

    public function testGetReturnsDefaultValueForNonExistingKey(): void
    {
        $data = ['key1' => 'value1'];
        $fixture = new DataGetterFixture($data);
        $this->assertSame('default', $fixture->get('key2', 'default'));
    }

    public function testHasReturnsKeyExistence(): void
    {
        $data = ['key1' => 'value1'];
        $fixture = new DataGetterFixture($data);
        $this->assertTrue($fixture->has('key1'));
        $this->assertFalse($fixture->has('key2'));
    }
}
