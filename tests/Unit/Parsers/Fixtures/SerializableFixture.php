<?php

namespace Formwork\Tests\Unit\Parsers\Fixtures;

class SerializableFixture
{
    public function __construct(private string $data) {}

    public function __serialize(): array
    {
        return ['data' => $this->data];
    }

    /**
     * @param array{data: string} $data
     */
    public function __unserialize(array $data): void
    {
        $this->data = $data['data'];
    }
}
