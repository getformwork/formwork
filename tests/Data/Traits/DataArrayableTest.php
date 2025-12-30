<?php

namespace Formwork\Tests\Data\Traits;

use Formwork\Data\Traits\DataArrayable;
use Formwork\Tests\Data\Fixtures\DataArrayableFixture;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversTrait;

#[CoversTrait(DataArrayable::class)]
final class DataArrayableTest extends TestCase
{
    public function testToArray(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $fixture = new DataArrayableFixture($data);
        $this->assertSame($data, $fixture->toArray());
    }
}
