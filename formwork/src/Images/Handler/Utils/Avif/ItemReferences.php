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

    public static function parse(string $body): self
    {
        $reader = new ByteReader($body);

        $version = $reader->uint(1);
        $reader->skip(3);
        $idSize = $version === 0 ? 2 : 4;
        $references = [];

        foreach (Box::parseAll($reader->bytes($reader->remaining())) as $box) {
            $boxReader = new ByteReader($box->body);
            $from = $boxReader->uint($idSize);
            $to = [];

            for ($count = $boxReader->uint(2); $count > 0; $count--) {
                $to[] = $boxReader->uint($idSize);
            }

            $references[] = ['type' => $box->type, 'from' => $from, 'to' => $to];
        }

        return new self($version, $references);
    }

    public function serialize(): string
    {
        $ids = array_merge(...array_map(static fn(array $reference) => [$reference['from'], ...$reference['to']], $this->references));

        // Version 0 supports 16-bit item IDs only, version 1 supports 32-bit ones
        $version = max([$this->version, ...$ids]) > 0xFFFF ? 1 : $this->version;
        $idSize = $version === 0 ? 2 : 4;

        $writer = (new ByteWriter())->uint($version, 1)->uint(0, 3);

        foreach ($this->references as $reference) {
            $body = (new ByteWriter())->uint($reference['from'], $idSize)->uint(count($reference['to']), 2);

            foreach ($reference['to'] as $id) {
                $body->uint($id, $idSize);
            }

            $writer->bytes((new Box($reference['type'], $body->toString()))->serialize());
        }

        return $writer->toString();
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
