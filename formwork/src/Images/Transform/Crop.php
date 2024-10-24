<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;
use RuntimeException;

class Crop extends AbstractTransform
{
    final public function __construct(protected int $originX, protected int $originY, protected int $width, protected int $height)
    {
    }

    public static function fromArray(array $data): static
    {
        return new static($data['originX'], $data['originY'], $data['width'], $data['height']);
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        if (($destinationImage = imagecreatetruecolor($this->width, $this->height)) === false) {
            throw new RuntimeException('Cannot create destination image');
        }

        $this->enableTransparency($destinationImage);

        imagecopy(
            $destinationImage,
            $gdImage,
            0,
            0,
            $this->originX,
            $this->originY,
            $this->width,
            $this->height
        ) ?: throw new RuntimeException('Cannot crop image');

        return $destinationImage;
    }

    protected function enableTransparency(GdImage $gdImage): void
    {
        if (($transparent = imagecolorallocatealpha($gdImage, 0, 0, 0, 127)) === false) {
            throw new RuntimeException('Cannot allocate transparent color');
        }
        imagealphablending($gdImage, true);
        imagesavealpha($gdImage, true);
        imagecolortransparent($gdImage, $transparent);
        imagefill($gdImage, 0, 0, $transparent);
    }
}
