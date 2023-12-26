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
        'Smooth' => [
            [0.075, 0.125, 0.075],
            [0.125, 0.200, 0.125],
            [0.075, 0.125, 0.075],
        ],

        'Mean' => [
            [1 / 9, 1 / 9, 1 / 9],
            [1 / 9, 1 / 9, 1 / 9],
            [1 / 9, 1 / 9, 1 / 9],
        ],

        'Gaussian' => [
            [0.075, 0.125, 0.075],
            [0.125, 0.200, 0.125],
            [0.075, 0.125, 0.075],
        ],
    ];

    protected int $amount;

    protected BlurMode $mode;

    final public function __construct(int $amount, BlurMode $mode)
    {
        if (!Constraint::isInIntegerRange($amount, 0, 100)) {
            throw new InvalidArgumentException(sprintf('$amount value must be in range 0-100, %d given', $amount));
        }

        if (!isset(self::CONVOLUTION_KERNELS[$mode->name])) {
            throw new InvalidArgumentException(sprintf('Invalid blur mode, "%s" given', $mode->name));
        }

        $this->amount = $amount;
        $this->mode = $mode;
    }

    public static function fromArray(array $data): static
    {
        return new static($data['amount'], $data['mode']);
    }

    public function apply(GdImage $image, ImageInfo $info): GdImage
    {
        for ($i = 0; $i < $this->amount; $i++) {
            imageconvolution($image, self::CONVOLUTION_KERNELS[$this->mode->name], 1, 0.55);
        }

        return $image;
    }
}
