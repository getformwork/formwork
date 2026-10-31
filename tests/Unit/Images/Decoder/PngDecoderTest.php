<?php

namespace Formwork\Tests\Unit\Images\Decoder;

use Formwork\Images\Decoder\PngDecoder;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\GdImages;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(PngDecoder::class)]
final class PngDecoderTest extends TestCase
{
    private const string HEADER = "\x89PNG\x0d\x0a\x1a\x0a";

    public function testChunksAreDecoded(): void
    {
        $data = self::HEADER . GdImages::pngChunk('IHDR', 'header') . GdImages::pngChunk('tEXt', 'ab') . GdImages::pngChunk('IEND', '');

        $chunks = iterator_to_array((new PngDecoder())->decode($data), false);

        $this->assertSame(['IHDR', 'tEXt', 'IEND'], array_column($chunks, 'type'));
        $this->assertSame(['header', 'ab', ''], array_column($chunks, 'value'));
        $this->assertSame([8, 26, 40], array_column($chunks, 'offset'));
        $this->assertSame([26, 40, 52], array_column($chunks, 'position'));
        $this->assertSame(crc32('IHDRheader'), $chunks[0]['checksum']);
    }

    public function testDataAfterTheEndChunkIsIgnored(): void
    {
        $data = self::HEADER . GdImages::pngChunk('IEND', '') . 'trailing garbage';

        $this->assertCount(1, iterator_to_array((new PngDecoder())->decode($data)));
    }

    public function testRealImagesAreDecoded(): void
    {
        $data = GdImages::png();

        $types = array_column(iterator_to_array((new PngDecoder())->decode($data), false), 'type');

        $this->assertSame('IHDR', $types[0]);
        $this->assertSame('IEND', end($types));
    }

    public function testInvalidHeadersAreNotDecoded(): void
    {
        $data = 'not a PNG image';

        $this->expectException(InvalidArgumentException::class);

        iterator_to_array((new PngDecoder())->decode($data));
    }

    public function testTruncatedChunkHeadersAreNotDecoded(): void
    {
        $data = self::HEADER . "\x00\x00";

        $this->expectException(UnexpectedValueException::class);

        iterator_to_array((new PngDecoder())->decode($data));
    }

    public function testChunksLongerThanTheDataAreNotDecoded(): void
    {
        $data = self::HEADER . pack('N', 100) . 'tEXtshort';

        $this->expectException(UnexpectedValueException::class);

        iterator_to_array((new PngDecoder())->decode($data));
    }

    public function testChunksWithoutChecksumAreNotDecoded(): void
    {
        $data = substr(self::HEADER . GdImages::pngChunk('tEXt', 'ab'), 0, -2);

        $this->expectException(UnexpectedValueException::class);

        iterator_to_array((new PngDecoder())->decode($data));
    }
}
