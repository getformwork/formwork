<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;
use RuntimeException;

final class Rotate extends AbstractTransform
{
    public function __construct(
        private float $angle,
    ) {}

    /**
     * @param array{angle: float, ...} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['angle']);
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        if (($backgroundColor = imagecolorallocatealpha($gdImage, 0, 0, 0, 127)) === false) {
            throw new RuntimeException('Cannot allocate background color');
        }
        return imagerotate($gdImage, $this->angle, $backgroundColor)
            ?: throw new RuntimeException('Cannot rotate image');
    }
}
