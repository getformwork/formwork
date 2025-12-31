<?php

namespace Formwork\Services\Exceptions;

use LogicException;
use Psr\Container\NotFoundExceptionInterface;

class ServiceNotFoundException extends LogicException implements NotFoundExceptionInterface {}
