<?php

namespace Formwork\Tests\Unit\Data\Traits;

use Formwork\Data\Traits\DataGetter;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Data\Fixtures\DataGetterFixture;
use PHPUnit\Framework\Attributes\CoversTrait;

#[CoversTrait(DataGetter::class)]
final class DataGetterTest extends TestCase
{
    public function testGetReturnsValueForExistingKey(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $dataGetterFixture = new DataGetterFixture($data);
        $this->assertSame('value1', $dataGetterFixture->get('key1'));
        $this->assertSame('value2', $dataGetterFixture->get('key2'));
    }

    public function testGetReturnsDefaultValueForNonExistingKey(): void
    {
        $data = ['key1' => 'value1'];
        $dataGetterFixture = new DataGetterFixture($data);
        $this->assertSame('default', $dataGetterFixture->get('key2', 'default'));
    }

    public function testHasReturnsKeyExistence(): void
    {
        $data = ['key1' => 'value1'];
        $dataGetterFixture = new DataGetterFixture($data);
        $this->assertTrue($dataGetterFixture->has('key1'));
        $this->assertFalse($dataGetterFixture->has('key2'));
    }
}
