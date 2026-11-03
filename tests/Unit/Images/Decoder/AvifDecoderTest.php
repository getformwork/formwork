<?php

namespace Formwork\Tests\Unit\Images\Decoder;

use Formwork\Images\Decoder\AvifDecoder;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(AvifDecoder::class)]
final class AvifDecoderTest extends TestCase
{
    public function testBoxesAreDecoded(): void
    {
        $data = $this->box('ftyp', 'avif') . $this->box('meta', 'abc') . $this->box('mdat', '');

        $boxes = iterator_to_array((new AvifDecoder())->decode($data), false);

        $this->assertSame(['ftyp', 'meta', 'mdat'], array_column($boxes, 'type'));
        $this->assertSame(['avif', 'abc', ''], array_column($boxes, 'value'));
        $this->assertSame([0, 12, 23], array_column($boxes, 'offset'));
        $this->assertSame([12, 23, 31], array_column($boxes, 'position'));
    }

    public function testBoxesWithALargeSizeAreDecoded(): void
    {
        $large = pack('N', 1) . 'free' . pack('J', 19) . 'abc';
        $data = $this->box('ftyp', 'avis') . $large;

        $boxes = iterator_to_array((new AvifDecoder())->decode($data), false);

        $this->assertSame('abc', $boxes[1]['value']);
        $this->assertSame(19, $boxes[1]['size']);
    }

    public function testBoxesExtendingToTheEndOfTheFileAreDecoded(): void
    {
        $data = $this->box('ftyp', 'avif') . pack('N', 0) . 'mdat' . 'payload';

        $boxes = iterator_to_array((new AvifDecoder())->decode($data), false);

        $this->assertSame('payload', $boxes[1]['value']);
        $this->assertSame(strlen($data), $boxes[1]['position']);
    }

    public function testTheEncodedFixtureIsDecoded(): void
    {
        $data = (string) file_get_contents(__DIR__ . '/../Fixtures/image.avif');

        $types = array_column(iterator_to_array((new AvifDecoder())->decode($data), false), 'type');

        $this->assertSame('ftyp', $types[0]);
        $this->assertContains('meta', $types);
    }

    public function testShortDataIsNotDecoded(): void
    {
        $data = 'short';

        $this->expectException(InvalidArgumentException::class);

        iterator_to_array((new AvifDecoder())->decode($data));
    }

    public function testDataWithoutTheFileTypeBoxIsNotDecoded(): void
    {
        $data = $this->box('meta', 'avif');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing ftyp box');

        iterator_to_array((new AvifDecoder())->decode($data));
    }

    public function testUnsupportedBrandsAreNotDecoded(): void
    {
        $data = $this->box('ftyp', 'heic');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unsupported brand');

        iterator_to_array((new AvifDecoder())->decode($data));
    }

    public function testBoxesLongerThanTheDataAreNotDecoded(): void
    {
        $data = $this->box('ftyp', 'avif') . pack('N', 100) . 'meta' . 'short';

        $this->expectException(UnexpectedValueException::class);

        iterator_to_array((new AvifDecoder())->decode($data));
    }

    public function testBoxesSmallerThanTheirHeaderAreNotDecoded(): void
    {
        $data = $this->box('ftyp', 'avif') . pack('N', 4) . 'meta';

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid box size');

        iterator_to_array((new AvifDecoder())->decode($data));
    }

    public function testTruncatedBoxHeadersAreNotDecoded(): void
    {
        $data = $this->box('ftyp', 'avif') . "\x00\x00";

        $this->expectException(UnexpectedValueException::class);

        iterator_to_array((new AvifDecoder())->decode($data));
    }

    private function box(string $type, string $payload): string
    {
        return pack('N', strlen($payload) + 8) . $type . $payload;
    }
}
