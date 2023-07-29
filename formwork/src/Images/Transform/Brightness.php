<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Utils\Constraint;
use GdImage;
use InvalidArgumentException;

class Brightness extends AbstractTransform
{
    protected int $amount;

    protected string $mode;

    public function __construct(int $amount)
    {
        if (!Constraint::isInIntegerRange($amount, -255, 255)) {
            throw new InvalidArgumentException(sprintf('$amount value must be in range -255-+255, %d given', $amount));
        }

        $this->amount = $amount;
    }

    public static function fromArray(array $data): static
    {
        return new self($data['amount']);
    }

    public function apply(GdImage $image, ImageInfo $info): GdImage
    {
        imagefilter($image, IMG_FILTER_BRIGHTNESS, $this->amount);
        return $image;
    }
}
