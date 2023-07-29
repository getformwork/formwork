<?php

namespace Formwork\Http\Session;

enum MessageType: string
{
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Error = 'error';
}
