<?php

namespace Formwork\Images\ColorProfile;

enum RenderingIntent
{
    case Perceptual;
    case MediaRelative;
    case Saturation;
    case IccAbsolute;
}
