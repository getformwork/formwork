<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;

class Scale extends AbstractTransform
{
    protected float $factor;

    final public function __construct(float $factor)
    {
        $this->factor = $factor;
    }

    public static function fromArray(array $data): static
    {
        return new static($data['factor']);
    }

    public function apply(GdImage $image, ImageInfo $info): GdImage
    {
        $resize = new Resize((int) floor(imagesx($image) * $this->factor), (int) floor(imagesy($image) * $this->factor));
        return $resize->apply($image, $info);
    }
}
