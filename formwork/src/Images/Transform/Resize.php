<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;

class Resize extends AbstractTransform
{
    protected int $width;

    protected int $height;

    protected ResizeMode $mode;

    final public function __construct(int $width, int $height, ResizeMode $mode = ResizeMode::Cover)
    {
        $this->width = $width;
        $this->height = $height;
        $this->mode = $mode;
    }

    public static function fromArray(array $data): static
    {
        return new static($data['width'], $data['height'], $data['mode']);
    }

    public function apply(GdImage $image, ImageInfo $info): GdImage
    {
        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);

        $cropAreaWidth = $sourceWidth;
        $cropAreaHeight = $sourceHeight;

        $cropOriginX = 0;
        $cropOriginY = 0;

        $destinationX = 0;
        $destinationY = 0;

        $sourceRatio = $sourceWidth / $sourceHeight;
        $destinationRatio = $this->width / $this->height;

        $destinationWidth = $this->width;
        $destinationHeight = $this->height;

        $width = $this->width;
        $height = $this->height;

        switch ($this->mode) {
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
                    $destinationWidth = $this->height * $sourceRatio;
                    $width = $destinationWidth;
                } else {
                    $destinationHeight = $this->width / $sourceRatio;
                    $height = $destinationHeight;
                }
                break;

            case ResizeMode::Center:
                if ($sourceRatio < $destinationRatio) {
                    $destinationWidth = $this->height * $sourceRatio;
                    $destinationX = ($this->width - $destinationWidth) / 2;
                } else {
                    $destinationHeight = $this->width / $sourceRatio;
                    $destinationY = ($this->height - $destinationHeight) / 2;
                }
                break;
        }

        $destinationImage = imagecreatetruecolor((int) $width, (int) $height);

        if ($info->hasAlphaChannel()) {
            $this->enableTransparency($destinationImage);
        }

        imagecopyresampled(
            $destinationImage,
            $image,
            (int) $destinationX,
            (int) $destinationY,
            (int) $cropOriginX,
            (int) $cropOriginY,
            (int) $destinationWidth,
            (int) $destinationHeight,
            (int) $cropAreaWidth,
            (int) $cropAreaHeight
        );

        return $destinationImage;
    }

    protected function enableTransparency(GdImage $image): void
    {
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagealphablending($image, true);
        imagesavealpha($image, true);
        imagecolortransparent($image, $transparent);
        imagefill($image, 0, 0, $transparent);
    }
}
