<?php

namespace Formwork\Tests\Unit\Images\Decoder;

use Formwork\Images\Decoder\WebpDecoder;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\GdImages;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(WebpDecoder::class)]
final class WebpDecoderTest extends TestCase
{
    public function testChunksAreDecoded(): void
    {
        $data = GdImages::webpFile(GdImages::webpChunk('VP8X', str_repeat("\x00", 10)), GdImages::webpChunk('EXIF', 'abc'));

        $chunks = iterator_to_array((new WebpDecoder())->decode($data), false);

        $this->assertSame(['VP8X', 'EXIF'], array_column($chunks, 'type'));
        $this->assertSame([12, 30], array_column($chunks, 'offset'));
        $this->assertSame([10, 3], array_column($chunks, 'size'));
        $this->assertSame([30, 42], array_column($chunks, 'position'));
    }

    public function testPaddingIsNotPartOfTheValue(): void
    {
        $data = GdImages::webpFile(GdImages::webpChunk('ICCP', 'abc'), GdImages::webpChunk('EXIF', 'de'));

        $chunks = iterator_to_array((new WebpDecoder())->decode($data), false);

        $this->assertSame(['abc', 'de'], array_column($chunks, 'value'));
        $this->assertSame(24, $chunks[0]['position']);
    }

    public function testMissingPaddingOfTheLastChunkIsTolerated(): void
    {
        $data = substr(GdImages::webpFile(GdImages::webpChunk('EXIF', 'abc')), 0, -1);

        $chunks = iterator_to_array((new WebpDecoder())->decode($data), false);

        $this->assertSame(['abc'], array_column($chunks, 'value'));
    }

    public function testDataAfterTheRiffContainerIsIgnored(): void
    {
        $data = GdImages::webpFile(GdImages::webpChunk('EXIF', 'ab')) . GdImages::webpChunk('EXIF', 'cd');

        $this->assertCount(1, iterator_to_array((new WebpDecoder())->decode($data)));
    }

    public function testRealImagesAreDecoded(): void
    {
        $data = GdImages::webp();

        $types = array_column(iterator_to_array((new WebpDecoder())->decode($data), false), 'type');

        $this->assertNotEmpty(array_intersect($types, ['VP8 ', 'VP8L']));
    }

    public function testInvalidHeadersAreNotDecoded(): void
    {
        $data = 'not a WebP image';

        $this->expectException(InvalidArgumentException::class);

        iterator_to_array((new WebpDecoder())->decode($data));
    }

    public function testRiffFilesOfAnotherTypeAreNotDecoded(): void
    {
        $data = 'RIFF' . pack('V', 4) . 'WAVE';

        $this->expectException(InvalidArgumentException::class);

        iterator_to_array((new WebpDecoder())->decode($data));
    }

    public function testChunksLongerThanTheDataAreNotDecoded(): void
    {
        $data = 'RIFF' . pack('V', 20) . 'WEBP' . 'EXIF' . pack('V', 100) . 'short';

        $this->expectException(UnexpectedValueException::class);

        iterator_to_array((new WebpDecoder())->decode($data));
    }
}
