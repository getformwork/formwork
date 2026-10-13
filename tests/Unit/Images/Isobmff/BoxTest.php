<?php

namespace Formwork\Tests\Unit\Images\Isobmff;

use Formwork\Images\Isobmff\Box;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\AvifFile;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Box::class)]
final class BoxTest extends TestCase
{
    public function testBoxesAreEncodedWithTheirSizeAndType(): void
    {
        $box = new Box('free', 'payload');

        $this->assertSame(8, $box->headerLength());
        $this->assertSame(15, $box->length());
        $this->assertSame(AvifFile::box('free', 'payload'), $box->encode());
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
        $boxes = Box::parseAll(AvifFile::box('abcd', 'first') . AvifFile::box('efgh', 'second'));

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
        $boxes = Box::parseAll(AvifFile::largeBox('mdat', 'payload') . AvifFile::box('free'));

        $this->assertCount(2, $boxes);
        $this->assertSame('mdat', $boxes[0]->type);
        $this->assertSame('payload', $boxes[0]->payload);
        $this->assertSame('free', $boxes[1]->type);
    }

    public function testBoxesWithoutASizeExtendToTheEndOfTheData(): void
    {
        $boxes = Box::parseAll(AvifFile::box('free') . AvifFile::unsizedBox('mdat', 'to the end'));

        $this->assertCount(2, $boxes);
        $this->assertSame('to the end', $boxes[1]->payload);
    }

    public function testSequencesOfBoxesAreEncoded(): void
    {
        $boxes = [new Box('abcd', 'first'), new Box('efgh', 'second')];

        $this->assertSame(AvifFile::box('abcd', 'first') . AvifFile::box('efgh', 'second'), Box::encodeAll($boxes));
    }

    public function testEncodingAndParsingAreSymmetrical(): void
    {
        $boxes = [new Box('abcd', 'first'), new Box('efgh', '')];

        $this->assertEquals($boxes, Box::parseAll(Box::encodeAll($boxes)));
    }
}
