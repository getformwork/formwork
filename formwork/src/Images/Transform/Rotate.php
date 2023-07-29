<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;

class Rotate extends AbstractTransform
{
    protected float $angle;

    public function __construct(float $angle)
    {
        $this->angle = $angle;
    }

    public static function fromArray(array $data): static
    {
        return new self($data['angle']);
    }

    public function apply(GdImage $image, ImageInfo $info): GdImage
    {
        $backgroundColor = imagecolorallocatealpha($image, 0, 0, 0, 127);
        $image = imagerotate($image, $this->angle, $backgroundColor);
        return $image;
    }
}
