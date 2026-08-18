<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Utils\Constraint;
use GdImage;
use InvalidArgumentException;

final class Brightness extends AbstractTransform
{
    public function __construct(
        private int $amount,
    ) {
        if (!Constraint::isInIntegerRange($amount, -255, 255)) {
            throw new InvalidArgumentException(sprintf('$amount value must be in range -255-+255, %d given', $amount));
        }
    }

    /**
     * @param array{amount: int, ...} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['amount']);
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        imagefilter($gdImage, IMG_FILTER_BRIGHTNESS, $this->amount);
        return $gdImage;
    }
}
