<?php

namespace Formwork\Tests\Unit\Images\Decoder;

use Formwork\Images\Decoder\GifDecoder;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\GdImages;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(GifDecoder::class)]
final class GifDecoderTest extends TestCase
{
    /**
     * Logical screen descriptor of a 3x2 image with a global color table of 2 colors
     */
    private const string SCREEN = "\x03\x00\x02\x00\x80\x00\x00";

    private const string COLOR_TABLE = "\x00\x00\x00\xff\xff\xff";

    private const string IMAGE = ",\x00\x00\x00\x00\x03\x00\x02\x00\x00\x02\x02\x44\x01\x00";

    public function testBlocksAreDecoded(): void
    {
        $extension = "!\xf9\x04\x01\x00\x00\x00\x00";
        $data = 'GIF89a' . self::SCREEN . self::COLOR_TABLE . $extension . self::IMAGE . ';';

        $blocks = iterator_to_array((new GifDecoder())->decode($data), false);

        $this->assertSame(['LSD', 'GCT', 'EXT', 'IMG', 'END'], array_column($blocks, 'type'));
        $this->assertSame([6, 13, 19, 27, 42], array_column($blocks, 'offset'));
        $this->assertSame([7, 6, 8, 15, 1], array_column($blocks, 'size'));
        $this->assertSame($extension, $blocks[2]['value']);
        $this->assertSame(0xf9, $blocks[2]['label']);
        $this->assertSame(self::IMAGE, $blocks[3]['value']);
        $this->assertSame(43, $blocks[4]['position']);
    }

    public function testScreenAndImageDescriptorsAreParsed(): void
    {
        $data = 'GIF87a' . self::SCREEN . self::COLOR_TABLE . self::IMAGE . ';';

        $blocks = iterator_to_array((new GifDecoder())->decode($data), false);

        $this->assertSame(3, $blocks[0]['desc']['width']);
        $this->assertSame(2, $blocks[0]['desc']['height']);
        $this->assertSame(1, $blocks[0]['desc']['gctflag']);
        $this->assertSame(0, $blocks[0]['desc']['gctsize']);
        $this->assertSame(3, $blocks[2]['desc']['width']);
        $this->assertSame(2, $blocks[2]['desc']['height']);
        $this->assertSame(0, $blocks[2]['desc']['lctflag']);
        $this->assertSame(2, $blocks[2]['desc']['lzwsize']);
    }

    public function testImagesWithoutGlobalColorTableAreDecoded(): void
    {
        $data = "GIF89a\x03\x00\x02\x00\x00\x00\x00" . self::IMAGE . ';';

        $blocks = iterator_to_array((new GifDecoder())->decode($data), false);

        $this->assertSame(['LSD', 'IMG', 'END'], array_column($blocks, 'type'));
    }

    public function testLocalColorTablesAreSkipped(): void
    {
        $image = ",\x00\x00\x00\x00\x03\x00\x02\x00\x80\x00\x00\x00\xff\xff\xff\x02\x02\x44\x01\x00";
        $data = "GIF89a\x03\x00\x02\x00\x00\x00\x00" . $image . ';';

        $blocks = iterator_to_array((new GifDecoder())->decode($data), false);

        $this->assertSame(['LSD', 'IMG', 'END'], array_column($blocks, 'type'));
        $this->assertSame($image, $blocks[1]['value']);
    }

    public function testDataAfterTheTrailerIsIgnored(): void
    {
        $data = "GIF89a\x03\x00\x02\x00\x00\x00\x00;trailing garbage";

        $this->assertCount(2, iterator_to_array((new GifDecoder())->decode($data)));
    }

    public function testRealImagesAreDecoded(): void
    {
        $data = GdImages::gif();

        $types = array_column(iterator_to_array((new GifDecoder())->decode($data), false), 'type');

        $this->assertSame('LSD', $types[0]);
        $this->assertContains('IMG', $types);
        $this->assertSame('END', end($types));
    }

    public function testInvalidHeadersAreNotDecoded(): void
    {
        $data = 'not a GIF image';

        $this->expectException(InvalidArgumentException::class);

        iterator_to_array((new GifDecoder())->decode($data));
    }

    public function testTruncatedScreenDescriptorsAreNotDecoded(): void
    {
        $data = "GIF89a\x03\x00";

        $this->expectException(UnexpectedValueException::class);

        iterator_to_array((new GifDecoder())->decode($data));
    }

    public function testTruncatedColorTablesAreNotDecoded(): void
    {
        $data = 'GIF89a' . self::SCREEN . "\x00\x00";

        $this->expectException(UnexpectedValueException::class);

        iterator_to_array((new GifDecoder())->decode($data));
    }

    public function testTruncatedBlocksAreNotDecoded(): void
    {
        $data = "GIF89a\x03\x00\x02\x00\x00\x00\x00" . substr(self::IMAGE, 0, -2);

        $this->expectException(UnexpectedValueException::class);

        iterator_to_array((new GifDecoder())->decode($data));
    }

    public function testUnknownBlockIntroducersAreNotDecoded(): void
    {
        $data = "GIF89a\x03\x00\x02\x00\x00\x00\x00?";

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unexpected block introducer');

        iterator_to_array((new GifDecoder())->decode($data));
    }
}
