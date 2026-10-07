<?php

namespace Formwork\Images\Handler\Utils\Avif;

/**
 * Item reference box (`iref`)
 *
 * @since 2.3.0
 */
final class ItemReferences
{
    /**
     * @param list<array{type: string, from: int, to: list<int>}> $references
     */
    public function __construct(
        private int $version = 0,
        private array $references = [],
    ) {}

    public static function parse(Box $box): self
    {
        $version = ord($box->body[0]);
        $idSize = $version === 0 ? 2 : 4;
        $references = [];

        foreach ($box->children as $child) {
            $reader = new ByteReader($child->body);
            $from = $reader->uint($idSize);
            $to = [];

            for ($count = $reader->uint(2); $count > 0; $count--) {
                $to[] = $reader->uint($idSize);
            }

            $references[] = ['type' => $child->type, 'from' => $from, 'to' => $to];
        }

        return new self($version, $references);
    }

    public function writeTo(Box $box): void
    {
        $ids = array_merge(...array_map(static fn(array $reference) => [$reference['from'], ...$reference['to']], $this->references));

        // Version 0 supports 16-bit item IDs only, version 1 supports 32-bit ones
        $version = max([$this->version, ...$ids]) > 0xFFFF ? 1 : $this->version;
        $idSize = $version === 0 ? 2 : 4;

        $box->body = (new ByteWriter())->uint($version, 1)->uint(0, 3)->toString();
        $box->children = [];

        foreach ($this->references as $reference) {
            $body = (new ByteWriter())->uint($reference['from'], $idSize)->uint(count($reference['to']), 2);

            foreach ($reference['to'] as $id) {
                $body->uint($id, $idSize);
            }

            $box->children[] = new Box($reference['type'], $body->toString());
        }
    }

    public function add(string $type, int $from, int $to): void
    {
        $this->references[] = ['type' => $type, 'from' => $from, 'to' => [$to]];
    }

    /**
     * Remove all the references from or to an item
     */
    public function remove(int $itemId): void
    {
        $references = [];

        foreach ($this->references as $reference) {
            $reference['to'] = array_values(array_diff($reference['to'], [$itemId]));

            if ($reference['from'] !== $itemId && $reference['to'] !== []) {
                $references[] = $reference;
            }
        }

        $this->references = $references;
    }
}
