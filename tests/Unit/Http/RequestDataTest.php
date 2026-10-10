<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\RequestData;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RequestData::class)]
final class RequestDataTest extends TestCase
{
    public function testRequestDataExposesItsValuesAndIterator(): void
    {
        $data = new RequestData(['first' => 'one', 'second' => 'two']);

        $this->assertFalse($data->isEmpty());
        $this->assertCount(2, $data);
        $this->assertSame('one', $data->get('first'));
        $this->assertSame('fallback', $data->get('missing', 'fallback'));
        $this->assertSame(['first' => 'one', 'second' => 'two'], $data->toArray());
    }
}
