<?php

namespace Formwork\Http;

enum ResponseStatusType
{
    case Informational;
    case Successful;
    case Redirection;
    case ClientError;
    case ServerError;
}
