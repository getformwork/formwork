<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Utils\Constraint;
use GdImage;
use InvalidArgumentException;

class Colorize extends AbstractTransform
{
    protected int $red;

    protected int $green;

    protected int $blue;

    protected int $alpha;

    public function __construct(int $red, int $green, int $blue, int $alpha)
    {
        if (!Constraint::isInIntegerRange($red, 0, 255)) {
            throw new InvalidArgumentException(sprintf('$red value must be in range 0-255, %d given', $red));
        }

        if (!Constraint::isInIntegerRange($green, 0, 255)) {
            throw new InvalidArgumentException(sprintf('$green value must be in range 0-255, %d given', $green));
        }

        if (!Constraint::isInIntegerRange($blue, 0, 255)) {
            throw new InvalidArgumentException(sprintf('$blue value must be in range 0-255, %d given', $blue));
        }

        if (!Constraint::isInIntegerRange($alpha, 0, 127)) {
            throw new InvalidArgumentException(sprintf('$alpha value must be in range 0-127, %d given', $alpha));
        }

        $this->red = $red;
        $this->green = $green;
        $this->blue = $blue;
        $this->alpha = $alpha;
    }

    public static function fromArray(array $data): static
    {
        return new self($data['red'], $data['green'], $data['blue'], $data['alpha']);
    }

    public function apply(GdImage $image, ImageInfo $info): GdImage
    {
        imagefilter($image, IMG_FILTER_COLORIZE, $this->red, $this->green, $this->blue, $this->alpha);
        return $image;
    }
}
