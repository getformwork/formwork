<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;
use InvalidArgumentException;

class Flip extends AbstractTransform
{
    protected const DIRECTIONS = [
        'HORIZONTAL' => IMG_FLIP_HORIZONTAL,
        'VERTICAL'   => IMG_FLIP_VERTICAL,
        'BOTH'       => IMG_FLIP_BOTH,
    ];

    protected FlipDirection $direction;

    public function __construct(FlipDirection $direction)
    {
        if (!isset(self::DIRECTIONS[$direction->name])) {
            throw new InvalidArgumentException(sprintf('Invalid flip direction, "%s" given', $direction));
        }

        $this->direction = $direction;
    }

    public static function fromArray(array $data): static
    {
        return new self($data['direction']);
    }

    public function apply(GdImage $image, ImageInfo $info): GdImage
    {
        imageflip($image, self::DIRECTIONS[$this->direction->name]);
        return $image;
    }
}
