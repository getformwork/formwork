<?php

namespace Formwork\Http\Session;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;

class Messages implements Arrayable
{
    use DataArrayable;

    /**
     * @var array<string, list<string>>
     *
     * Session messages data
     */
    protected array $data = [];

    /**
     * @param array<string, list<string>> $data
     */
    public function __construct(array &$data)
    {
        $this->data = &$data;
    }

    /**
     * Return whether there are messages of a given type
     */
    public function has(MessageType $messageType): bool
    {
        return !empty($this->data[$messageType->value]);
    }

    /**
     * Get messages of a given type
     *
     * @return list<string>
     */
    public function get(MessageType $messageType): array
    {
        $messages = $this->data[$messageType->value] ?? [];
        $this->remove($messageType);
        return $messages;
    }

    /**
     * Get messages of all types
     *
     * @return array<string, list<string>>
     */
    public function getAll(): array
    {
        $messages = $this->data;
        $this->removeAll();
        return $messages;
    }

    /**
     * Set messages of a given type
     *
     * @param list<string>|string $messages
     */
    public function set(MessageType $messageType, string|array $messages): void
    {
        $this->data[$messageType->value] = (array) $messages;
    }

    /**
     * Add a message of a given type
     */
    public function add(MessageType $messageType, string $message): void
    {
        if (empty($this->data[$messageType->value])) {
            $this->set($messageType, []);
        }
        $this->data[$messageType->value][] = $message;
    }

    /**
     * Remove messages of a given type
     */
    public function remove(MessageType $messageType): void
    {
        unset($this->data[$messageType->value]);
    }

    /**
     * Remove all messages
     */
    public function removeAll(): void
    {
        $this->data = [];
    }
}
