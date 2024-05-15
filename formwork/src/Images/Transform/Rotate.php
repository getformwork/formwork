<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;
use RuntimeException;

class Rotate extends AbstractTransform
{
    final public function __construct(protected float $angle)
    {
    }

    public static function fromArray(array $data): static
    {
        return new static($data['angle']);
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        if (($backgroundColor = imagecolorallocatealpha($gdImage, 0, 0, 0, 127)) === false) {
            throw new RuntimeException('Cannot allocate background color');
        }
        return imagerotate($gdImage, $this->angle, $backgroundColor)
            ?: throw new RuntimeException('Cannot rotate image');
    }
}
