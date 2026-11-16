<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\EdgeDetect;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EdgeDetect::class)]
final class EdgeDetectTest extends TestCase
{
    public function testEdgesAreHighlighted(): void
    {
        // A light pixel on a dark gray background shows the effect of the kernel
        $gdImage = imagecreatetruecolor(3, 3);
        imagefill($gdImage, 0, 0, 0x404040);
        imagesetpixel($gdImage, 1, 1, 0xC0C0C0);

        $gdImage = (new EdgeDetect())->apply($gdImage, new ImageInfo(['hasAlphaChannel' => false]));

        $this->assertSame([255, 127], [
            imagecolorat($gdImage, 1, 1) & 0xFF,
            imagecolorat($gdImage, 1, 0) & 0xFF,
        ]);
    }
}
