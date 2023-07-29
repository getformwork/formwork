<?php

namespace Formwork\Images\Transform;

enum FlipDirection: string
{
    case Horizontal = 'horizontal';
    case Vertical = 'vertical';
    case Both = 'both';
}
