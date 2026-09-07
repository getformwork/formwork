<?php

namespace Formwork\Images\Transform;

enum BlurMode: string
{
    case Smooth = 'smooth';
    case Mean = 'mean';
    case Gaussian = 'gaussian';
}
