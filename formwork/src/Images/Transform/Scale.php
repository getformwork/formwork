<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;

class Scale extends AbstractTransform
{
    protected float $factor;

    public function __construct(float $factor)
    {
        $this->factor = $factor;
    }

    public static function fromArray(array $data): static
    {
        return new self($data['factor']);
    }

    public function apply(GdImage $image, ImageInfo $info): GdImage
    {
        $resize = new Resize(floor(imagesx($image) * $this->factor), floor(imagesy($image) * $this->factor));
        return $resize->apply($image, $info);
    }
}
