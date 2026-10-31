<?php

namespace Formwork\Tests\Unit\Images\Decoder;

use Formwork\Images\Decoder\SvgDecoder;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(SvgDecoder::class)]
final class SvgDecoderTest extends TestCase
{
    /**
     * @param ?array{width: int, height: int} $expected
     */
    #[DataProvider('dimensionsProvider')]
    public function testDimensionsAreDecoded(string $attributes, ?array $expected): void
    {
        $data = sprintf('<svg xmlns="http://www.w3.org/2000/svg" %s></svg>', $attributes);

        $decoded = iterator_to_array((new SvgDecoder())->decode($data), false);

        $this->assertSame($expected === null ? [] : [$expected], $decoded);
    }

    /**
     * @return iterable<string, array{string, ?array{width: int, height: int}}>
     */
    public static function dimensionsProvider(): iterable
    {
        yield 'unitless' => ['width="100" height="50"', ['width' => 100, 'height' => 50]];
        yield 'pixels' => ['width="100px" height="50.4px"', ['width' => 100, 'height' => 50]];
        yield 'points' => ['width="72pt" height="36pt"', ['width' => 96, 'height' => 48]];
        yield 'inches' => ['width="2in" height="1in"', ['width' => 192, 'height' => 96]];
        yield 'view box' => ['viewBox="0 0 640 480"', ['width' => 640, 'height' => 480]];
        yield 'view box with commas' => ['viewBox="0,0,64,48"', ['width' => 64, 'height' => 48]];
        yield 'percentages' => ['width="100%" height="100%" viewBox="0 0 20 10"', ['width' => 20, 'height' => 10]];
        yield 'width only' => ['width="200" viewBox="0 0 20 10"', ['width' => 200, 'height' => 100]];
        yield 'height only' => ['height="100" viewBox="0 0 20 10"', ['width' => 200, 'height' => 100]];
        yield 'no dimensions' => ['', null];
        yield 'invalid view box' => ['viewBox="0 0 a b"', null];
        yield 'relative units' => ['width="10em" height="5em"', null];
    }

    public function testPrefixedRootElementsAreDecoded(): void
    {
        $data = '<s:svg xmlns:s="http://www.w3.org/2000/svg" width="10" height="20"/>';

        $this->assertSame([['width' => 10, 'height' => 20]], iterator_to_array((new SvgDecoder())->decode($data), false));
    }

    public function testMalformedXmlIsNotDecodedAndRaisesNoWarnings(): void
    {
        $data = '<svg width="10"';

        $this->expectException(InvalidArgumentException::class);

        iterator_to_array((new SvgDecoder())->decode($data));
    }

    #[DataProvider('invalidDataProvider')]
    public function testInvalidDataIsNotDecoded(string $data): void
    {
        $this->expectException(InvalidArgumentException::class);

        iterator_to_array((new SvgDecoder())->decode($data));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidDataProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'another root element' => ['<html width="10" height="10"/>'];
        yield 'not xml' => ['not an SVG image'];
    }
}
