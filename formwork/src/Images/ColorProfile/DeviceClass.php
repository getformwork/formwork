<?php

namespace Formwork\Images\ColorProfile;

enum DeviceClass
{
    case Input;
    case Display;
    case Output;
    case Link;
    case ColorSpace;
    case AbstractProfile;
    case NamedColor;
}
