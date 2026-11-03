<?php

namespace Formwork\Tests\Unit\Images\Decoder;

use Formwork\Images\Decoder\JpegDecoder;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\GdImages;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(JpegDecoder::class)]
final class JpegDecoderTest extends TestCase
{
    public function testSegmentsAreDecoded(): void
    {
        $data = "\xff\xd8" . GdImages::jpegSegment(0xe1, 'abc') . "\xff\xd9";

        $segments = iterator_to_array((new JpegDecoder())->decode($data), false);

        $this->assertSame([0xd8, 0xe1, 0xd9], array_column($segments, 'type'));
        $this->assertSame([null, 'abc', null], array_column($segments, 'value'));
        $this->assertSame([0, 2, 9], array_column($segments, 'offset'));
        $this->assertSame([2, 9, 11], array_column($segments, 'position'));
    }

    public function testScansIncludeTheirEntropyCodedData(): void
    {
        $header = "\x01\x01\x00\x00\x3f\x00";
        $scan = "\x12\xff\x00\x34\xff\xd0\x56";
        $data = "\xff\xd8" . GdImages::jpegSegment(0xda, $header) . $scan . GdImages::jpegSegment(0xfe, 'comment') . "\xff\xd9";

        $segments = iterator_to_array((new JpegDecoder())->decode($data), false);

        $this->assertSame([0xd8, 0xda, 0xfe, 0xd9], array_column($segments, 'type'));
        $this->assertSame($header . $scan, $segments[1]['value']);
        $this->assertSame($segments[2]['offset'], $segments[1]['position']);
    }

    public function testPaddingBeforeMarkersIsSkipped(): void
    {
        $data = "\xff\xd8\xff\xff\xff" . chr(0xfe) . pack('n', 4) . 'ab' . "\xff\xd9";

        $segments = iterator_to_array((new JpegDecoder())->decode($data), false);

        $this->assertSame([0xd8, 0xfe, 0xd9], array_column($segments, 'type'));
        $this->assertSame('ab', $segments[1]['value']);
    }

    public function testDataAfterTheEndOfImageMarkerIsIgnored(): void
    {
        $data = "\xff\xd8\xff\xd9" . 'trailing garbage';

        $this->assertCount(2, iterator_to_array((new JpegDecoder())->decode($data)));
    }

    public function testRealImagesAreDecoded(): void
    {
        $data = GdImages::jpeg();

        $types = array_column(iterator_to_array((new JpegDecoder())->decode($data), false), 'type');

        $this->assertSame(0xd8, $types[0]);
        $this->assertContains(0xda, $types);
        $this->assertSame(0xd9, end($types));
    }

    public function testDataWithoutStartOfImageMarkerIsNotDecoded(): void
    {
        $data = 'not a JPEG image';

        $this->expectException(InvalidArgumentException::class);

        iterator_to_array((new JpegDecoder())->decode($data));
    }

    public function testTruncatedMarkersAreNotDecoded(): void
    {
        $data = "\xff\xd8\xff";

        $this->expectException(UnexpectedValueException::class);

        iterator_to_array((new JpegDecoder())->decode($data));
    }

    public function testSegmentsLongerThanTheDataAreNotDecoded(): void
    {
        $data = "\xff\xd8\xff\xe1" . pack('n', 100) . 'short';

        $this->expectException(UnexpectedValueException::class);

        iterator_to_array((new JpegDecoder())->decode($data));
    }

    public function testSegmentsWithAnInvalidSizeAreNotDecoded(): void
    {
        $data = "\xff\xd8\xff\xe1\x00\x01\x00";

        $this->expectException(UnexpectedValueException::class);

        iterator_to_array((new JpegDecoder())->decode($data));
    }

    public function testUnterminatedScansAreNotDecoded(): void
    {
        $data = "\xff\xd8" . GdImages::jpegSegment(0xda, "\x01\x01") . "\x12\x34";

        $this->expectException(UnexpectedValueException::class);

        iterator_to_array((new JpegDecoder())->decode($data));
    }

    public function testDataWithoutMarkersAfterTheStartIsNotDecoded(): void
    {
        $data = "\xff\xd8\x00\x00";

        $this->expectException(InvalidArgumentException::class);

        iterator_to_array((new JpegDecoder())->decode($data));
    }
}
