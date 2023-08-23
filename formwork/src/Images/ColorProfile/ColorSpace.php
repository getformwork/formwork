<?php

namespace Formwork\Images\ColorProfile;

enum ColorSpace: string
{
    case XYZ = 'XYZ';
    case LAB = 'LAB';
    case LUV = 'LUV';
    case YCbCr = 'YCbCr';
    case XYY = 'XYY';
    case RGB = 'RGB';
    case Grayscale = 'Grayscale';
    case HSV = 'HSV';
    case HLS = 'HLS';
    case CMYK = 'CMYK';
    case CMY = 'CMY';
    case Palette = 'Palette';
}
