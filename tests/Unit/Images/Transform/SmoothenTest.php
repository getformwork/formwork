<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\Smoothen;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Smoothen::class)]
final class SmoothenTest extends TestCase
{
    public function testImagesAreSmoothened(): void
    {
        // A single white pixel on black shows how much it is spread to the surrounding pixels
        $gdImage = imagecreatetruecolor(3, 3);
        imagesetpixel($gdImage, 1, 1, 0xFFFFFF);

        $gdImage = (new Smoothen())->apply($gdImage, new ImageInfo(['hasAlphaChannel' => false]));

        $this->assertSame([85, 21, 21], [
            imagecolorat($gdImage, 1, 1) & 0xFF,
            imagecolorat($gdImage, 1, 0) & 0xFF,
            imagecolorat($gdImage, 0, 0) & 0xFF,
        ]);
    }
}
