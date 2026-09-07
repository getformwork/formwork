<?php

namespace Formwork\Images\Transform;

use Formwork\Data\Contracts\ArraySerializable;
use Formwork\Images\ImageInfo;
use GdImage;

interface TransformInterface extends ArraySerializable
{
    /**
     * Apply the transform to a GdImage instance
     */
    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage;

    /**
     * Get a string specifier encoding transform arguments
     */
    public function getSpecifier(): string;
}
