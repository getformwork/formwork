<?php

namespace Formwork\Images\Transform;

use Formwork\Data\Contracts\ArraySerializable;
use Formwork\Images\ImageInfo;
use GdImage;

interface TransformInterface extends ArraySerializable
{
    public function apply(GdImage $image, ImageInfo $info): GdImage;

    public function getSpecifier(): string;
}
