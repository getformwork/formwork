<?php

namespace Formwork\Tests\Data\Traits;

use Formwork\Data\Traits\DataCountable;
use Formwork\Tests\Data\Fixtures\DataCountableFixture;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversTrait;

#[CoversTrait(DataCountable::class)]
final class DataCountableTest extends TestCase
{
    public function testCount(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'];
        $fixture = new DataCountableFixture($data);
        $this->assertCount(3, $fixture);
    }
}
