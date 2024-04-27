<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;

class Smoothen extends AbstractTransform
{
    final public function __construct()
    {
    }

    public static function fromArray(array $data): static
    {
        return new static();
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        imagefilter($gdImage, IMG_FILTER_SMOOTH);
        return $gdImage;
    }
}
