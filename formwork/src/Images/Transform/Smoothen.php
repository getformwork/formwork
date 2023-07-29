<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;

class Smoothen extends AbstractTransform
{
    public static function fromArray(array $data): static
    {
        return new self();
    }

    public function apply(GdImage $image, ImageInfo $info): GdImage
    {
        imagefilter($image, IMG_FILTER_SMOOTH);
        return $image;
    }
}
