<?php

namespace Formwork\Images\Avif;

use UnexpectedValueException;

/**
 * ISO base media file format box
 *
 * @since 2.3.0
 */
final class Box
{
    /**
     * Boxes containing other boxes, with the length of the data preceding their children
     */
    private const array CONTAINERS = ['meta' => 4, 'iprp' => 0, 'ipco' => 0];

    /**
     * @param string    $type       Four-character box type
     * @param string    $body       Box payload, or the data preceding the children for container boxes
     * @param list<Box> $children   Child boxes (container boxes only)
     * @param int|null  $bodyOffset Offset of the payload in the parsed file
     */
    public function __construct(
        public string $type,
        public string $body = '',
        public array $children = [],
        public readonly ?int $bodyOffset = null,
    ) {}

    /**
     * Create a box from its payload, parsing the children of container boxes
     */
    public static function create(string $type, string $body, ?int $bodyOffset = null): self
    {
        $prefixLength = self::CONTAINERS[$type] ?? null;

        if ($prefixLength === null) {
            return new self($type, $body, [], $bodyOffset);
        }

        return new self($type, substr($body, 0, $prefixLength), self::parseAll(substr($body, $prefixLength)), $bodyOffset);
    }

    /**
     * Parse a sequence of boxes
     *
     * @return list<Box>
     */
    public static function parseAll(string $data): array
    {
        $reader = new ByteReader($data);
        $boxes = [];

        while (!$reader->isAtEnd()) {
            $size = $reader->uint(4);
            $type = $reader->bytes(4);
            $headerSize = 8;

            if ($size === 1) {
                $size = $reader->uint(8);
                $headerSize = 16;
            } elseif ($size === 0) {
                $size = $reader->remaining() + $headerSize;
            }

            if ($size < $headerSize) {
                throw new UnexpectedValueException('Invalid box size');
            }

            $boxes[] = self::create($type, $reader->bytes($size - $headerSize));
        }

        return $boxes;
    }

    /**
     * Get the first child of the given type
     */
    public function child(string $type): ?self
    {
        foreach ($this->children as $child) {
            if ($child->type === $type) {
                return $child;
            }
        }

        return null;
    }

    /**
     * Get all the children of the given type
     *
     * @return list<Box>
     */
    public function childrenOfType(string $type): array
    {
        return array_values(array_filter($this->children, static fn(self $child) => $child->type === $type));
    }

    /**
     * Get a reader of the payload of a full box, positioned after its version and flags
     */
    public function fullBoxReader(): ByteReader
    {
        $reader = new ByteReader($this->body);
        $reader->skip(4);

        return $reader;
    }

    public function size(): int
    {
        return $this->headerSize() + $this->payloadSize();
    }

    public function headerSize(): int
    {
        return 8 + $this->payloadSize() > 0xFFFFFFFF ? 16 : 8;
    }

    public function serialize(): string
    {
        $payload = $this->body . implode('', array_map(static fn(self $child) => $child->serialize(), $this->children));

        $header = $this->headerSize() === 8
            ? (new ByteWriter())->uint(8 + strlen($payload), 4)->bytes($this->type)
            : (new ByteWriter())->uint(1, 4)->bytes($this->type)->uint(16 + strlen($payload), 8);

        return $header->toString() . $payload;
    }

    private function payloadSize(): int
    {
        return strlen($this->body) + array_sum(array_map(static fn(self $child) => $child->size(), $this->children));
    }
}
