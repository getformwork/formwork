<?php

namespace Formwork\Tests\Parsers\Fixtures;

class SerializableFixture
{
    private string $data;

    public function __construct(string $data)
    {
        $this->data = $data;
    }

    public function __serialize(): array
    {
        return ['data' => $this->data];
    }

    public function __unserialize(array $data): void
    {
        $this->data = $data['data'];
    }
}
