<?php

namespace Formwork\Tests\Data\Traits;

use Formwork\Data\Traits\DataIterator;
use Formwork\Tests\Data\Fixtures\DataIteratorFixture;
use PHPUNit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

#[CoversTrait(DataIterator::class)]
final class DataIteratorTest extends TestCase
{
    public function testIterator(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'];
        $fixture = new DataIteratorFixture($data);
        $iteratedData = [];
        foreach ($fixture as $key => $value) {
            $iteratedData[$key] = $value;
        }
        $this->assertSame($data, $iteratedData);
    }
}
