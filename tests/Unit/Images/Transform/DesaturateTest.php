<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\Desaturate;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Desaturate::class)]
final class DesaturateTest extends TestCase
{
    public function testImagesAreMadeGrayFromTheLuminanceOfTheirColors(): void
    {
        $gdImage = imagecreatetruecolor(1, 1);
        imagesetpixel($gdImage, 0, 0, 0x804020);

        $gdImage = (new Desaturate())->apply($gdImage, new ImageInfo(['hasAlphaChannel' => false]));

        $this->assertSame(0x4F4F4F, imagecolorat($gdImage, 0, 0));
    }
}
