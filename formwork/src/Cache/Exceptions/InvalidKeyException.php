<?php

namespace Formwork\Cache\Exceptions;

use InvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

class InvalidKeyException extends InvalidArgumentException implements PsrInvalidArgumentException {}
