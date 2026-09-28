<?php

namespace Formwork\Tests\Unit\Data\Traits;

use Formwork\Data\Traits\DataArrayable;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Data\Fixtures\DataArrayableFixture;
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
