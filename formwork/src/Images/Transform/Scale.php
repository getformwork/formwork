<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;

class Scale extends AbstractTransform
{
    final public function __construct(protected float $factor)
    {
    }

    public static function fromArray(array $data): static
    {
        return new static($data['factor']);
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        $resize = new Resize((int) floor(imagesx($gdImage) * $this->factor), (int) floor(imagesy($gdImage) * $this->factor));
        return $resize->apply($gdImage, $imageInfo);
    }
}
