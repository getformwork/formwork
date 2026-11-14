<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\Resize;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Resize::class)]
final class ResizeTest extends TestCase
{
    public function testMissingDimensionsAreComputedForEachImage(): void
    {
        $resize = new Resize(width: 50);
        $specifier = $resize->getSpecifier();

        $portrait = $resize->apply(imagecreatetruecolor(100, 200), new ImageInfo(['hasAlphaChannel' => false]));
        $landscape = $resize->apply(imagecreatetruecolor(200, 100), new ImageInfo(['hasAlphaChannel' => false]));

        $this->assertSame([50, 100], [imagesx($portrait), imagesy($portrait)]);
        $this->assertSame([50, 25], [imagesx($landscape), imagesy($landscape)]);

        // The transform is not changed, so it identifies the same processed images in the cache
        $this->assertSame($specifier, $resize->getSpecifier());
    }
}
