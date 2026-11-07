<?php

namespace Formwork\Tests\Unit\Images\Isobmff;

use Formwork\Images\Isobmff\Box;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\AvifFileFixture;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Box::class)]
final class BoxTest extends TestCase
{
    public function testBoxesAreEncodedWithTheirSizeAndType(): void
    {
        $box = new Box('free', 'payload');

        $this->assertSame(8, $box->headerLength());
        $this->assertSame(15, $box->length());
        $this->assertSame(AvifFileFixture::box('free', 'payload'), $box->encode());
    }

    public function testFullBoxHeadersHoldAVersionAndThreeFlagBytes(): void
    {
        $this->assertSame("\x01\x00\x00\x00", Box::fullBoxHeader(1));
        $this->assertSame("\x02\x00\x00\x07", Box::fullBoxHeader(2, 7));
    }

    public function testEmptyBoxesOnlyHaveAHeader(): void
    {
        $box = new Box('free');

        $this->assertSame(8, $box->length());
        $this->assertSame(pack('N', 8) . 'free', $box->encode());
    }

    public function testBoxesAreTheirOwnGenericRepresentation(): void
    {
        $box = new Box('free');

        $this->assertSame($box, $box->toBox());
    }

    public function testSequencesOfBoxesAreParsed(): void
    {
        $boxes = Box::parseAll(AvifFileFixture::box('abcd', 'first') . AvifFileFixture::box('efgh', 'second'));

        $this->assertCount(2, $boxes);
        $this->assertSame('abcd', $boxes[0]->type);
        $this->assertSame('first', $boxes[0]->payload);
        $this->assertSame('efgh', $boxes[1]->type);
        $this->assertSame('second', $boxes[1]->payload);
    }

    public function testEmptySequencesHaveNoBoxes(): void
    {
        $this->assertSame([], Box::parseAll(''));
    }

    public function testBoxesWithA64BitSizeAreParsed(): void
    {
        $boxes = Box::parseAll(AvifFileFixture::largeBox('mdat', 'payload') . AvifFileFixture::box('free'));

        $this->assertCount(2, $boxes);
        $this->assertSame('mdat', $boxes[0]->type);
        $this->assertSame('payload', $boxes[0]->payload);
        $this->assertSame('free', $boxes[1]->type);
    }

    public function testBoxesWithoutASizeExtendToTheEndOfTheData(): void
    {
        $boxes = Box::parseAll(AvifFileFixture::box('free') . AvifFileFixture::unsizedBox('mdat', 'to the end'));

        $this->assertCount(2, $boxes);
        $this->assertSame('to the end', $boxes[1]->payload);
    }

    public function testSequencesOfBoxesAreEncoded(): void
    {
        $boxes = [new Box('abcd', 'first'), new Box('efgh', 'second')];

        $this->assertSame(AvifFileFixture::box('abcd', 'first') . AvifFileFixture::box('efgh', 'second'), Box::encodeAll($boxes));
    }

    public function testEncodingAndParsingAreSymmetrical(): void
    {
        $boxes = [new Box('abcd', 'first'), new Box('efgh', '')];

        $this->assertEquals($boxes, Box::parseAll(Box::encodeAll($boxes)));
    }
}
