<?php

namespace Formwork\Tests\Unit\Images;

use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\ImageInfo;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(ImageInfo::class)]
final class ImageInfoTest extends TestCase
{
    public function testInfoIsReadFromTheGivenProperties(): void
    {
        $info = [
            'mimeType'             => 'image/gif',
            'width'                => 40,
            'height'               => 30,
            'colorSpace'           => ColorSpace::Palette,
            'colorDepth'           => 8,
            'colorNumber'          => 256,
            'hasAlphaChannel'      => true,
            'isAnimation'          => true,
            'animationFrames'      => 12,
            'animationRepeatCount' => 0,
        ];

        $imageInfo = new ImageInfo($info);

        $this->assertSame($info, $imageInfo->toArray());
        $this->assertSame(array_values($info), [
            $imageInfo->mimeType(),
            $imageInfo->width(),
            $imageInfo->height(),
            $imageInfo->colorSpace(),
            $imageInfo->colorDepth(),
            $imageInfo->colorNumber(),
            $imageInfo->hasAlphaChannel(),
            $imageInfo->isAnimation(),
            $imageInfo->animationFrames(),
            $imageInfo->animationRepeatCount(),
        ]);
    }

    public function testOptionalInfoIsMissingByDefault(): void
    {
        $imageInfo = new ImageInfo(['mimeType' => 'image/jpeg', 'width' => 40, 'height' => 30, 'hasAlphaChannel' => false, 'isAnimation' => false]);

        $this->assertNull($imageInfo->colorSpace());
        $this->assertNull($imageInfo->colorDepth());
        $this->assertNull($imageInfo->colorNumber());
        $this->assertNull($imageInfo->animationFrames());
        $this->assertNull($imageInfo->animationRepeatCount());
    }

    public function testUnknownPropertiesAreRejected(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid property "depth"');
        new ImageInfo(['depth' => 8]);
    }
}
