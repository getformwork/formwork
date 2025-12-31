<?php

namespace Formwork\Services\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class ServiceResolutionException extends RuntimeException implements ContainerExceptionInterface {}
