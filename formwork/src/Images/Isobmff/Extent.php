<?php

namespace Formwork\Images\Isobmff;

/**
 * @since 2.4.0
 */
final readonly class Extent
{
    public function __construct(
        public int $offset,
        public int $length,
        public int $index = 0,
    ) {}

    public function shifted(int $delta): self
    {
        return new self($this->offset + $delta, $this->length, $this->index);
    }
}
