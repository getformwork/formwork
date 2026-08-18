<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

final class Crop extends AbstractTransform
{
    public function __construct(
        private int $originX,
        private int $originY,
        private int $width,
        private int $height,
    ) {
        if ($originX < 0) {
            throw new InvalidArgumentException('$originX must be greater than or equal to 0');
        }
        if ($originY < 0) {
            throw new InvalidArgumentException('$originY must be greater than or equal to 0');
        }
        if ($width <= 0) {
            throw new InvalidArgumentException('$width must be greater than 0');
        }
        if ($height <= 0) {
            throw new InvalidArgumentException('$height must be greater than 0');
        }
    }

    /**
     * @param array{originX: int, originY: int, width: int, height: int, ...} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['originX'], $data['originY'], $data['width'], $data['height']);
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        if ($this->width <= 0) {
            throw new UnexpectedValueException('Unexpected non-positive width');
        }

        if ($this->height <= 0) {
            throw new UnexpectedValueException('Unexpected non-positive height');
        }

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

    private function enableTransparency(GdImage $gdImage): void
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
