<?php

namespace Formwork\Cache\Exceptions;

use InvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

class InvalidNamespaceException extends InvalidArgumentException implements PsrInvalidArgumentException {}
