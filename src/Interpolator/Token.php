<?php

namespace Formwork\Interpolator;

use Stringable;

class Token implements Stringable
{
    /**
     * Identifier token type
     */
    public const string TYPE_IDENTIFIER = 'identifier';

    /**
     * Number token type
     */
    public const string TYPE_NUMBER = 'number';

    /**
     * String token type
     */
    public const string TYPE_STRING = 'string';

    /**
     * Punctuation token type
     */
    public const string TYPE_PUNCTUATION = 'punctuation';

    /**
     * Arrow token type
     */
    public const string TYPE_ARROW = 'arrow';

    /**
     * End token type
     */
    public const string TYPE_END = 'end';

    public function __construct(
        protected string $type,
        protected ?string $value,
        protected int $position,
    ) {}

    public function __toString(): string
    {
        return sprintf(
            'token%s of type %s',
            $this->value === null ? '' : ' "' . $this->value . '"',
            $this->type
        );
    }

    /**
     * Get token type
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Get token value
     */
    public function value(): ?string
    {
        return $this->value;
    }

    /**
     * Get token position
     */
    public function position(): int
    {
        return $this->position;
    }

    /**
     * Test if token matches the given type and value
     */
    public function test(string $type, ?string $value = null): bool
    {
        if (func_num_args() < 2) {
            return $this->type === $type;
        }
        return $this->type === $type && $this->value === $value;
    }
}
