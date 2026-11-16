<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\Pixelate;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Pixelate::class)]
final class PixelateTest extends TestCase
{
    public function testBlocksTakeTheColorOfTheirFirstPixel(): void
    {
        $gdImage = imagecreatetruecolor(4, 4);
        imagesetpixel($gdImage, 0, 0, 0xFF0000);
        imagesetpixel($gdImage, 3, 3, 0x00FF00);

        $gdImage = (new Pixelate(2))->apply($gdImage, new ImageInfo(['hasAlphaChannel' => false]));

        $this->assertSame([0xFF0000, 0xFF0000, 0x000000, 0x000000], [
            imagecolorat($gdImage, 0, 0),
            imagecolorat($gdImage, 1, 1),
            imagecolorat($gdImage, 2, 2),
            imagecolorat($gdImage, 3, 3),
        ]);
    }
}
