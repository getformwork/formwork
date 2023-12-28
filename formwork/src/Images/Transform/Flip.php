<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;
use InvalidArgumentException;

class Flip extends AbstractTransform
{
    protected const DIRECTIONS = [
        'Horizontal' => IMG_FLIP_HORIZONTAL,
        'Vertical'   => IMG_FLIP_VERTICAL,
        'Both'       => IMG_FLIP_BOTH,
    ];

    protected FlipDirection $direction;

    final public function __construct(FlipDirection $flipDirection)
    {
        if (!isset(self::DIRECTIONS[$flipDirection->name])) {
            throw new InvalidArgumentException(sprintf('Invalid flip direction, "%s" given', $flipDirection->name));
        }

        $this->direction = $flipDirection;
    }

    public static function fromArray(array $data): static
    {
        return new static($data['direction']);
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        imageflip($gdImage, self::DIRECTIONS[$this->direction->name]);
        return $gdImage;
    }
}
