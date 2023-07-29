<?php

namespace Formwork\Http;

enum RequestType: string
{
    case Http = 'HTTP';
    case XmlHttpRequest = 'XHR';
}
