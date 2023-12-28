<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;

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
        $backgroundColor = imagecolorallocatealpha($gdImage, 0, 0, 0, 127);
        return imagerotate($gdImage, $this->angle, $backgroundColor);
    }
}
