<?php

namespace Formwork\Images\Decoder;

use Generator;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * @since 2.3.0
 */
final class AvifDecoder implements DecoderInterface
{
    /**
     * File type box identifier
     */
    private const string FTYP_BOX = 'ftyp';

    /**
     * Valid AVIF major brands
     */
    private const array VALID_BRANDS = ['avif', 'avis'];

    /**
     * Boxes containing other boxes, with the length of the data preceding their children
     *
     * The length for `iinf` boxes depends on their version, see {@see AvifDecoder::getPrefixLength()}
     */
    private const array CONTAINER_BOXES = ['meta' => 4, 'iprp' => 0, 'ipco' => 0, 'iref' => 4];

    /**
     * Decode the top-level boxes of an AVIF file
     *
     * The value of container boxes (like `meta`) is the data preceding their children, which are
     * decoded recursively and returned in the `children` key (`null` for other boxes)
     *
     * @return Generator<int, array<string, mixed>>
     */
    public function decode(string &$data): Generator
    {
        if (strlen($data) < 12) {
            throw new InvalidArgumentException('Invalid AVIF data');
        }

        if (substr($data, 4, 4) !== self::FTYP_BOX) {
            throw new InvalidArgumentException('Invalid AVIF data: missing ftyp box');
        }

        if (!in_array(substr($data, 8, 4), self::VALID_BRANDS, true)) {
            throw new InvalidArgumentException('Invalid AVIF data: unsupported brand');
        }

        $position = 0;
        $length = strlen($data);

        while ($position < $length) {
            $box = $this->decodeBox($data, $position, $length);

            $position += $box['size'];

            yield [...$box, 'position' => &$position];
        }
    }

    /**
     * Decode the box starting at $offset, which must end before $end
     *
     * @return array{offset: int, size: int, headerSize: int, type: string, value: string, children: list<array<string, mixed>>|null}
     */
    private function decodeBox(string $data, int $offset, int $end): array
    {
        if ($end - $offset < 8) {
            throw new UnexpectedValueException('Invalid AVIF data: truncated box');
        }

        $size = $this->unpack('N', $data, $offset)[1];
        $type = substr($data, $offset + 4, 4);
        $headerSize = 8;

        if ($size === 1) {
            if ($end - $offset < 16) {
                throw new UnexpectedValueException('Invalid AVIF data: truncated box');
            }

            $size = $this->unpack('J', $data, $offset + 8)[1];
            $headerSize = 16;
        } elseif ($size === 0) {
            $size = $end - $offset;
        }

        if ($size < $headerSize || $size > $end - $offset) {
            throw new UnexpectedValueException('Invalid AVIF data: invalid box size');
        }

        $contentOffset = $offset + $headerSize;
        $contentEnd = $offset + $size;
        $prefixLength = $this->getPrefixLength($type, $data, $contentOffset, $contentEnd);

        $box = ['offset' => $offset, 'size' => $size, 'headerSize' => $headerSize, 'type' => $type];

        if ($prefixLength === null) {
            return [...$box, 'value' => substr($data, $contentOffset, $contentEnd - $contentOffset), 'children' => null];
        }

        $children = [];
        $position = $contentOffset + $prefixLength;

        while ($position < $contentEnd) {
            $child = $this->decodeBox($data, $position, $contentEnd);
            $children[] = $child;
            $position += $child['size'];
        }

        return [...$box, 'value' => substr($data, $contentOffset, $prefixLength), 'children' => $children];
    }

    /**
     * Get the length of the data preceding the children of a box, or null if the box is not a container
     */
    private function getPrefixLength(string $type, string $data, int $contentOffset, int $contentEnd): ?int
    {
        // Version and flags, followed by the entry count (16 bits in version 0, 32 bits otherwise)
        $prefixLength = $type === 'iinf'
            ? ($contentEnd > $contentOffset ? (ord($data[$contentOffset]) === 0 ? 6 : 8) : 8)
            : (self::CONTAINER_BOXES[$type] ?? null);

        if ($prefixLength !== null && $prefixLength > $contentEnd - $contentOffset) {
            throw new UnexpectedValueException('Invalid AVIF data: truncated box');
        }

        return $prefixLength;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function unpack(string $format, string $string, int $offset = 0): array
    {
        return unpack($format, $string, $offset) ?: throw new UnexpectedValueException('Cannot unpack string');
    }
}
