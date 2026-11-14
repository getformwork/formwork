<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

final class Resize extends AbstractTransform
{
    public function __construct(
        private ?int $width = null,
        private ?int $height = null,
        private ResizeMode $resizeMode = ResizeMode::Cover,
    ) {
        if ($width === null && $height === null) {
            throw new InvalidArgumentException('At least one of $width or $height must be specified');
        }

        if ($width !== null && $width <= 0) {
            throw new InvalidArgumentException('$width must be greater than 0');
        }

        if ($height !== null && $height <= 0) {
            throw new InvalidArgumentException('$height must be greater than 0');
        }
    }

    /**
     * @param array{width: int|null, height: int|null, mode: ResizeMode, ...} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['width'], $data['height'], $data['mode']);
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        $sourceWidth = imagesx($gdImage);
        $sourceHeight = imagesy($gdImage);

        $cropAreaWidth = $sourceWidth;
        $cropAreaHeight = $sourceHeight;

        $cropOriginX = 0;
        $cropOriginY = 0;

        $destinationX = 0;
        $destinationY = 0;

        $sourceRatio = $sourceWidth / $sourceHeight;

        // A missing dimension is computed from the ratio of the image, without changing the transform, which could be applied to other images
        $targetWidth = $this->width ?? (int) (($this->height ?? $sourceHeight) * $sourceRatio);
        $targetHeight = $this->height ?? (int) ($targetWidth / $sourceRatio);

        $destinationRatio = $targetWidth / $targetHeight;

        $destinationWidth = $targetWidth;
        $destinationHeight = $targetHeight;

        $width = $targetWidth;
        $height = $targetHeight;

        switch ($this->resizeMode) {
            case ResizeMode::Fill:
                $cropAreaWidth = $sourceWidth;
                $cropAreaHeight = $sourceHeight;
                break;

            case ResizeMode::Cover:
                if ($sourceRatio > $destinationRatio) {
                    $cropAreaWidth = $sourceHeight * $destinationRatio;
                    $cropOriginX = ($sourceWidth - $cropAreaWidth) / 2;
                } else {
                    $cropAreaHeight = $sourceWidth / $destinationRatio;
                    $cropOriginY = ($sourceHeight - $cropAreaHeight) / 2;
                }
                break;

            case ResizeMode::Contain:
                if ($sourceRatio < $destinationRatio) {
                    $destinationWidth = $targetHeight * $sourceRatio;
                    $width = (int) $destinationWidth;
                } else {
                    $destinationHeight = $targetWidth / $sourceRatio;
                    $height = (int) $destinationHeight;
                }
                break;

            case ResizeMode::Center:
                if ($sourceRatio < $destinationRatio) {
                    $destinationWidth = $targetHeight * $sourceRatio;
                    $destinationX = ($targetWidth - $destinationWidth) / 2;
                } else {
                    $destinationHeight = $targetWidth / $sourceRatio;
                    $destinationY = ($targetHeight - $destinationHeight) / 2;
                }
                break;
        }

        if ($width <= 0) {
            throw new UnexpectedValueException('Unexpected non-positive calculated width');
        }

        if ($height <= 0) {
            throw new UnexpectedValueException('Unexpected non-positive calculated height');
        }

        $destinationImage = imagecreatetruecolor($width, $height);

        if ($destinationImage === false) {
            throw new RuntimeException('Cannot create destination image');
        }

        if ($imageInfo->hasAlphaChannel()) {
            $this->enableTransparency($destinationImage);
        }

        imagecopyresampled(
            $destinationImage,
            $gdImage,
            (int) $destinationX,
            (int) $destinationY,
            (int) $cropOriginX,
            (int) $cropOriginY,
            (int) $destinationWidth,
            (int) $destinationHeight,
            (int) $cropAreaWidth,
            (int) $cropAreaHeight
        ) ?: throw new RuntimeException('Cannot resize image');

        return $destinationImage;
    }

    private function enableTransparency(GdImage $gdImage): void
    {
        if (($transparent = imagecolorallocatealpha($gdImage, 0, 0, 0, 127)) === false) {
            throw new RuntimeException('Cannot allocate transparent color');
        }
        imagealphablending($gdImage, true);
        imagesavealpha($gdImage, true);
        imagecolortransparent($gdImage, $transparent);
        imagefill($gdImage, 0, 0, $transparent);
    }
}
