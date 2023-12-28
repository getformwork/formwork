<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;

class Pixelate extends AbstractTransform
{
    final public function __construct(protected int $amount)
    {
    }

    public static function fromArray(array $data): static
    {
        return new static($data['amount']);
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        imagefilter($gdImage, IMG_FILTER_PIXELATE, $this->amount);
        return $gdImage;
    }
}
