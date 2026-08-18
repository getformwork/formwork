<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;

final class Flip extends AbstractTransform
{
    /**
     * Flip directions
     *
     * @var array<string, int>
     */
    private const array DIRECTIONS = [
        'Horizontal' => IMG_FLIP_HORIZONTAL,
        'Vertical'   => IMG_FLIP_VERTICAL,
        'Both'       => IMG_FLIP_BOTH,
    ];

    public function __construct(
        private FlipDirection $flipDirection,
    ) {}

    /**
     * @param array{direction: FlipDirection, ...} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['direction']);
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        imageflip($gdImage, self::DIRECTIONS[$this->flipDirection->name]);
        return $gdImage;
    }
}
