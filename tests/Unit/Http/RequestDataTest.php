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
        $requestData = new RequestData(['first' => 'one', 'second' => 'two']);

        $this->assertFalse($requestData->isEmpty());
        $this->assertCount(2, $requestData);
        $this->assertSame('one', $requestData->get('first'));
        $this->assertSame('fallback', $requestData->get($this->unchecked('missing'), 'fallback'));
        $this->assertSame(['first' => 'one', 'second' => 'two'], $requestData->toArray());
    }
}
