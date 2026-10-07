<?php

namespace Formwork\Images\Handler\Utils\Avif;

/**
 * Item information box (`iinf`)
 *
 * @since 2.3.0
 */
final class ItemInfo
{
    /**
     * @param list<Box> $entries Item information entries (`infe` boxes)
     */
    public function __construct(
        private int $version,
        private array $entries,
    ) {}

    public static function parse(Box $box): self
    {
        return new self(ord($box->body[0]), $box->children);
    }

    public function writeTo(Box $box): void
    {
        $version = count($this->entries) > 0xFFFF ? 1 : $this->version;

        $box->body = (new ByteWriter())->uint($version, 1)->uint(0, 3)->uint(count($this->entries), $version === 0 ? 2 : 4)->toString();
        $box->children = $this->entries;
    }

    /**
     * Get the ID of the first item of the given type
     */
    public function findByType(string $type): ?int
    {
        foreach ($this->entries as $entry) {
            $item = self::readEntry($entry);

            if ($item['type'] === $type) {
                return $item['id'];
            }
        }

        return null;
    }

    /**
     * Get the greatest item ID
     */
    public function maxId(): int
    {
        return max([0, ...array_map(static fn(Box $entry) => self::readEntry($entry)['id'], $this->entries)]);
    }

    public function add(int $itemId, string $type): void
    {
        // Version 2 supports 16-bit item IDs only, version 3 supports 32-bit ones
        $version = $itemId > 0xFFFF ? 3 : 2;

        $this->entries[] = new Box('infe', (new ByteWriter())
            ->uint($version, 1)
            ->uint(0, 3)
            ->uint($itemId, $version === 2 ? 2 : 4)
            ->uint(0, 2)
            ->bytes($type)
            ->bytes("\x00")
            ->toString());
    }

    public function remove(int $itemId): void
    {
        $this->entries = array_values(array_filter($this->entries, static fn(Box $entry) => self::readEntry($entry)['id'] !== $itemId));
    }

    /**
     * @return array{id: int, type: string}
     */
    private static function readEntry(Box $entry): array
    {
        $reader = new ByteReader($entry->body);

        $version = $reader->uint(1);
        $reader->skip(3);
        $id = $reader->uint($version < 3 ? 2 : 4);
        $reader->skip(2);

        // Item type is not present in versions 0 and 1
        return ['id' => $id, 'type' => $version < 2 ? '' : $reader->bytes(4)];
    }
}
