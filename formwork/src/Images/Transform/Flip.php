<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;

class Flip extends AbstractTransform
{
    protected const DIRECTIONS = [
        'Horizontal' => IMG_FLIP_HORIZONTAL,
        'Vertical'   => IMG_FLIP_VERTICAL,
        'Both'       => IMG_FLIP_BOTH,
    ];

    final public function __construct(protected FlipDirection $flipDirection)
    {
    }

    public static function fromArray(array $data): static
    {
        return new static($data['direction']);
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        imageflip($gdImage, self::DIRECTIONS[$this->flipDirection->name]);
        return $gdImage;
    }
}
