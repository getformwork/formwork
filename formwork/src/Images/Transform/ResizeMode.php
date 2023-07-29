<?php

namespace Formwork\Images\Transform;

enum ResizeMode: string
{
    case Fill = 'fill';
    case Cover = 'cover';
    case Contain = 'contain';
    case Center = 'center';
}
