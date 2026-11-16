<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\Resize;
use Formwork\Images\Transform\Scale;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use UnexpectedValueException;

#[CoversClass(Scale::class)]
#[UsesClass(Resize::class)]
final class ScaleTest extends TestCase
{
    public function testScaledDimensionsAreRoundedDown(): void
    {
        $gdImage = (new Scale(0.5))->apply(imagecreatetruecolor(11, 7), new ImageInfo(['hasAlphaChannel' => false]));

        $this->assertSame([5, 3], [imagesx($gdImage), imagesy($gdImage)]);
    }

    public function testFactorMustBePositive(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Scale factor must be greater than 0');
        new Scale(0);
    }

    public function testImagesCannotBeScaledToNothing(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unexpected non-positive calculated height');
        (new Scale(0.1))->apply(imagecreatetruecolor(20, 5), new ImageInfo(['hasAlphaChannel' => false]));
    }
}
