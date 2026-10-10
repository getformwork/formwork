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
        $headers = new HeadersData(['x_custom_header' => 'value', 'content-type' => 'text/plain']);

        $this->assertSame('value', $headers->get('X-Custom-Header'));
        $this->assertNull($headers->get('x_custom_header'));
        $this->assertSame(['Content-Type' => 'text/plain', 'X-Custom-Header' => 'value'], $headers->toArray());
    }
}
