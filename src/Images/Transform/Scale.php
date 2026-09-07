<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;
use InvalidArgumentException;
use UnexpectedValueException;

final class Scale extends AbstractTransform
{
    public function __construct(
        private float $factor,
    ) {
        if ($factor <= 0) {
            throw new InvalidArgumentException('Scale factor must be greater than 0');
        }
    }

    /**
     * @param array{factor: float, ...} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['factor']);
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        $width = (int) floor(imagesx($gdImage) * $this->factor);
        $height = (int) floor(imagesy($gdImage) * $this->factor);

        if ($width <= 0) {
            throw new UnexpectedValueException('Unexpected non-positive calculated width');
        }

        if ($height <= 0) {
            throw new UnexpectedValueException('Unexpected non-positive calculated height');
        }

        $resize = new Resize($width, $height);
        return $resize->apply($gdImage, $imageInfo);
    }
}
