<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;

class Pixelate extends AbstractTransform
{
    protected int $amount;

    final public function __construct(int $amount)
    {
        $this->amount = $amount;
    }

    public static function fromArray(array $data): static
    {
        return new static($data['amount']);
    }

    public function apply(GdImage $image, ImageInfo $info): GdImage
    {
        imagefilter($image, IMG_FILTER_PIXELATE, $this->amount);
        return $image;
    }
}
