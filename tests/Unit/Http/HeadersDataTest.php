<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\HeadersData;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HeadersData::class)]
final class HeadersDataTest extends TestCase
{
    public function testHeadersAreNormalizedAndSorted(): void
    {
        $headersData = new HeadersData(['x_custom_header' => 'value', 'content-type' => 'text/plain']);

        $this->assertSame('value', $headersData->get('X-Custom-Header'));
        $this->assertNull($headersData->get('x_custom_header'));
        $this->assertSame(['Content-Type' => 'text/plain', 'X-Custom-Header' => 'value'], $headersData->toArray());
    }
}
