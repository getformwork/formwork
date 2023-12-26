<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;

class Crop extends AbstractTransform
{
    protected int $originX;

    protected int $originY;

    protected int $width;

    protected int $height;

    final public function __construct(int $originX, int $originY, int $width, int $height)
    {
        $this->originX = $originX;
        $this->originY = $originY;
        $this->width = $width;
        $this->height = $height;
    }

    public static function fromArray(array $data): static
    {
        return new static($data['originX'], $data['originY'], $data['width'], $data['height']);
    }

    public function apply(GdImage $image, ImageInfo $info): GdImage
    {
        $destinationImage = imagecreatetruecolor($this->width, $this->height);

        $this->enableTransparency($destinationImage);

        imagecopy(
            $destinationImage,
            $image,
            0,
            0,
            $this->originX,
            $this->originY,
            $this->width,
            $this->height
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
