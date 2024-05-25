<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Utils\Constraint;
use GdImage;
use InvalidArgumentException;

class Blur extends AbstractTransform
{
    /**
     * Convolution kernels used for image effects
     */
    protected const CONVOLUTION_KERNELS = [
        BlurMode::Smooth->value => [
            [0.075, 0.125, 0.075],
            [0.125, 0.200, 0.125],
            [0.075, 0.125, 0.075],
        ],

        BlurMode::Mean->value => [
            [1 / 9, 1 / 9, 1 / 9],
            [1 / 9, 1 / 9, 1 / 9],
            [1 / 9, 1 / 9, 1 / 9],
        ],

        BlurMode::Gaussian->value => [
            [0.075, 0.125, 0.075],
            [0.125, 0.200, 0.125],
            [0.075, 0.125, 0.075],
        ],
    ];

    protected int $amount;

    final public function __construct(int $amount, protected BlurMode $blurMode)
    {
        if (!Constraint::isInIntegerRange($amount, 0, 100)) {
            throw new InvalidArgumentException(sprintf('$amount value must be in range 0-100, %d given', $amount));
        }

        $this->amount = $amount;
    }

    public static function fromArray(array $data): static
    {
        return new static($data['amount'], $data['mode']);
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        for ($i = 0; $i < $this->amount; $i++) {
            imageconvolution($gdImage, self::CONVOLUTION_KERNELS[$this->blurMode->value], 1, 0.55);
        }

        return $gdImage;
    }
}
