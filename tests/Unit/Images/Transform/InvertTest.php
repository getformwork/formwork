<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\Invert;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Invert::class)]
final class InvertTest extends TestCase
{
    public function testColorsAreInverted(): void
    {
        $gdImage = imagecreatetruecolor(1, 1);
        imagesetpixel($gdImage, 0, 0, 0x804020);

        $gdImage = (new Invert())->apply($gdImage, new ImageInfo(['hasAlphaChannel' => false]));

        $this->assertSame(0x7FBFDF, imagecolorat($gdImage, 0, 0));
    }
}
