<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Header;
use Formwork\Http\Utils\Header as ResponseHeader;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(Header::class)]
final class HeaderTest extends TestCase
{
    public function testHeaderParsingAndFormatting(): void
    {
        $tokens = Header::split('for=1;proto=https', ';=');

        $this->assertSame([['for', '1'], ['proto', 'https']], $tokens);
        $this->assertSame(['for' => '1', 'proto' => 'https'], Header::combine($tokens));
        $this->assertSame(['application/json' => 1.0, 'text/html' => 0.8], Header::parseQualityValues('text/html;q=.8, application/json'));
        $this->assertSame('X-Custom-Header', Header::fixHeaderName('x_custom_header'));
        $this->assertSame(['X-Test' => 'value'], Header::fixHeaderNames(['x_test' => 'value']));

        $this->expectException(UnexpectedValueException::class);
        Header::combine([[]]);
    }

    public function testResponseHeaderFormatting(): void
    {
        $this->assertSame('a=b; c', ResponseHeader::make(['a' => 'b', 'c']));
    }
}
