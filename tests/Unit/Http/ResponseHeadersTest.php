<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\ResponseHeaders;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ResponseHeaders::class)]
final class ResponseHeadersTest extends TestCase
{
    public function testResponseHeadersNormalizeSetRemoveAndSortValues(): void
    {
        $responseHeaders = new ResponseHeaders(['z-header' => 'last', 'a-header' => 'first']);
        $responseHeaders->set('cache_control', 'no-cache');
        $responseHeaders->remove('Z-HEADER');

        $this->assertSame(['A-Header' => 'first', 'Cache-Control' => 'no-cache'], $responseHeaders->toArray());
        $this->assertTrue($responseHeaders->has('cache-control'));
    }

    public function testHeadersAreReadWithAnyNameCase(): void
    {
        $responseHeaders = new ResponseHeaders(['Content-Type' => 'text/plain']);

        $this->assertSame('text/plain', $responseHeaders->get('content-type'));
        $this->assertSame('default', $responseHeaders->get('x-missing', 'default'));
    }

    public function testHeadersAreEmptyOnlyWithoutValues(): void
    {
        $this->assertTrue((new ResponseHeaders([]))->isEmpty());
        $this->assertFalse((new ResponseHeaders(['Content-Type' => 'text/plain']))->isEmpty());
    }
}
