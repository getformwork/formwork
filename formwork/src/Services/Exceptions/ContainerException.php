<?php

namespace Formwork\Services\Exceptions;

use LogicException;
use Psr\Container\ContainerExceptionInterface;

class ContainerException extends LogicException implements ContainerExceptionInterface {}
