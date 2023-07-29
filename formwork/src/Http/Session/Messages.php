<?php

namespace Formwork\Http\Session;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;

class Messages implements Arrayable
{
    use DataArrayable;

    protected array $data = [];

    public function __construct(array &$data)
    {
        $this->data = &$data;
    }

    public function has(MessageType $type): bool
    {
        return !empty($this->data[$type->value]);
    }

    public function get(MessageType $type): array
    {
        $messages = $this->data[$type->value] ?? [];
        $this->remove($type);
        return $messages;
    }

    public function getAll(): array
    {
        $messages = $this->data;
        $this->removeAll();
        return $messages;
    }

    public function set(MessageType $type, $messages): void
    {
        $this->data[$type->value] = (array) $messages;
    }

    public function add(MessageType $type, $message): void
    {
        if (empty($this->data[$type->value])) {
            $this->set($type, []);
        }
        $this->data[$type->value][] = $message;
    }

    public function remove(MessageType $type): void
    {
        unset($this->data[$type->value]);
    }

    public function removeAll(): void
    {
        $this->data = [];
    }
}
