<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;

final class Smoothen extends AbstractTransform
{
    /**
     * Weight of the center pixel, compared to the weight of 1 of each surrounding pixel
     */
    private const int WEIGHT = 4;

    public static function fromArray(array $data): self
    {
        return new self();
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        imagefilter($gdImage, IMG_FILTER_SMOOTH, self::WEIGHT);
        return $gdImage;
    }
}
