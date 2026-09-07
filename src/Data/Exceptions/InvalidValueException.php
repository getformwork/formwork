<?php

namespace Formwork\Data\Exceptions;

use RuntimeException;
use Throwable;

class InvalidValueException extends RuntimeException
{
    /**
     * @param ?array<string, mixed> $context
     */
    public function __construct(
        string $message,
        protected ?string $identifier = null,
        protected ?array $context = [],
        int $code = 0,
        ?Throwable $throwable = null,
    ) {
        parent::__construct($message, $code, $throwable);
    }

    /**
     * Get the identifier of the invalid value
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * Get the context of the invalid value
     *
     * @return ?array<string, mixed>
     *
     * @since 2.3.0
     */
    public function getContext(): ?array
    {
        return $this->context;
    }
}
