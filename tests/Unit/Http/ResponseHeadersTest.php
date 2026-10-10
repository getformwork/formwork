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
        $headers = new ResponseHeaders(['z-header' => 'last', 'a-header' => 'first']);
        $headers->set('cache_control', 'no-cache');
        $headers->remove('Z-HEADER');

        $this->assertSame(['A-Header' => 'first', 'Cache-Control' => 'no-cache'], $headers->toArray());
        $this->assertTrue($headers->has('cache-control'));
    }
}
