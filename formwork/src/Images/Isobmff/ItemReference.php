<?php

namespace Formwork\Images\Isobmff;

use Formwork\Images\Binary\ByteReader;
use Formwork\Images\Binary\Bytes;

/**
 * @since 2.4.0
 */
final readonly class ItemReference
{
    /**
     * @param list<int> $to
     */
    public function __construct(
        public string $type,
        public int $from,
        public array $to,
    ) {}

    public static function fromBox(Box $box, int $idSize): self
    {
        $reader = new ByteReader($box->payload);
        $from = $reader->uint($idSize);
        $to = [];

        for ($count = $reader->uint(2); $count > 0; $count--) {
            $to[] = $reader->uint($idSize);
        }

        return new self($box->type, $from, $to);
    }

    /**
     * Get all the item identifiers involved in the reference
     *
     * @return list<int>
     */
    public function ids(): array
    {
        return [$this->from, ...$this->to];
    }

    public function toBox(int $idSize): Box
    {
        $payload = Bytes::pack($this->from, $idSize) . Bytes::pack(count($this->to), 2);

        foreach ($this->to as $id) {
            $payload .= Bytes::pack($id, $idSize);
        }

        return new Box($this->type, $payload);
    }

    /**
     * Get a copy of the reference without the given item, or null if nothing is left to reference
     */
    public function withoutItem(int $id): ?self
    {
        $to = array_values(array_diff($this->to, [$id]));

        return $this->from === $id || $to === [] ? null : new self($this->type, $this->from, $to);
    }
}
