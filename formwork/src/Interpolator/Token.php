<?php

namespace Formwork\Interpolator;

class Token
{
    /**
     * Identifier token type
     */
    public const TYPE_IDENTIFIER = 'identifier';

    /**
     * Number token type
     */
    public const TYPE_NUMBER = 'number';

    /**
     * String token type
     */
    public const TYPE_STRING = 'string';

    /**
     * Punctuation token type
     */
    public const TYPE_PUNCTUATION = 'punctuation';

    /**
     * Arrow token type
     */
    public const TYPE_ARROW = 'arrow';

    /**
     * End token type
     */
    public const TYPE_END = 'end';

    /**
     * Token type
     */
    protected string $type;

    /**
     * Token value
     */
    protected ?string $value;

    /**
     * Token position
     */
    protected int $position;

    public function __construct(string $type, ?string $value, int $position)
    {
        $this->type = $type;
        $this->value = $value;
        $this->position = $position;
    }

    public function __toString()
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
