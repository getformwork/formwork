<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;

final class Desaturate extends AbstractTransform
{
    public static function fromArray(array $data): self
    {
        return new self();
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        imagefilter($gdImage, IMG_FILTER_GRAYSCALE);
        return $gdImage;
    }
}
