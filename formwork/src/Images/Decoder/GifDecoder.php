<?php

namespace Formwork\Images\Decoder;

use Formwork\Images\Binary\ByteReader;
use Formwork\Images\Binary\Endianness;
use Generator;
use InvalidArgumentException;
use UnexpectedValueException;

final class GifDecoder implements DecoderInterface
{
    /**
     * GIF file headers
     *
     * @var array<string>
     */
    private const array GIF_HEADERS = ['GIF87a', 'GIF89a'];

    /**
     * Block introducers
     */
    private const string IMAGE_SEPARATOR = ',';

    private const string EXTENSION_INTRODUCER = '!';

    private const string TRAILER = ';';

    public function decode(string &$data): Generator
    {
        if (!in_array(substr($data, 0, 6), self::GIF_HEADERS, true)) {
            throw new InvalidArgumentException('Invalid GIF data');
        }

        $reader = new ByteReader($data, 6);

        $desc = $this->parseLogicalScreenDescriptor($reader);

        yield $this->block($data, 6, $reader->position(), 'LSD', $desc);

        if ($desc['gctflag'] === 1) {
            $offset = $reader->position();
            $reader->skip($this->getColorTableSize($desc['gctsize']));

            yield $this->block($data, $offset, $reader->position(), 'GCT');
        }

        while ($reader->remaining() > 0) {
            $offset = $reader->position();
            $separator = $reader->bytes(1);

            switch ($separator) {
                case self::IMAGE_SEPARATOR:
                    $desc = $this->parseImageDescriptor($reader);
                    if ($desc['lctflag'] === 1) {
                        $reader->skip($this->getColorTableSize($desc['lctsize']));
                    }
                    $desc['lzwsize'] = $reader->uint(1);
                    $this->skipSubBlocks($reader);

                    yield $this->block($data, $offset, $reader->position(), 'IMG', $desc);
                    break;

                case self::EXTENSION_INTRODUCER:
                    $label = $reader->uint(1);
                    $this->skipSubBlocks($reader);

                    yield $this->block($data, $offset, $reader->position(), 'EXT', null, $label);
                    break;

                case self::TRAILER:
                    yield $this->block($data, $offset, $reader->position(), 'END');
                    return;

                default:
                    throw new UnexpectedValueException('Unexpected block introducer');
            }
        }
    }

    /**
     * Build a decoded block
     *
     * @param ?array<string, int> $desc
     *
     * @return array<string, mixed>
     */
    private function block(string &$data, int $offset, int $position, string $type, ?array $desc = null, ?int $label = null): array
    {
        return [
            'offset'   => $offset,
            'size'     => $position - $offset,
            'type'     => $type,
            'label'    => $label,
            'desc'     => $desc,
            'value'    => substr($data, $offset, $position - $offset),
            'position' => $position,
        ];
    }

    /**
     * Skip the sub-blocks of a block, including the block terminator
     */
    private function skipSubBlocks(ByteReader $reader): void
    {
        while (($size = $reader->uint(1)) > 0) {
            $reader->skip($size);
        }
    }

    /**
     * Parse logical screen descriptor
     *
     * @return array<string, int>
     */
    private function parseLogicalScreenDescriptor(ByteReader $reader): array
    {
        $width = $reader->uint(2, Endianness::Little);
        $height = $reader->uint(2, Endianness::Little);
        $packed = $reader->uint(1);

        return [
            'width'    => $width,
            'height'   => $height,
            'packed'   => $packed,
            'gctflag'  => ($packed & 0x80) >> 7,
            'colorres' => ($packed & 0x70) >> 4,
            'sflag'    => ($packed & 0x08) >> 3,
            'gctsize'  => ($packed & 0x07),
            'bgindex'  => $reader->uint(1),
            'pxratio'  => $reader->uint(1),
        ];
    }

    /**
     * Parse image descriptor
     *
     * @return array<string, int>
     */
    private function parseImageDescriptor(ByteReader $reader): array
    {
        $left = $reader->uint(2, Endianness::Little);
        $top = $reader->uint(2, Endianness::Little);
        $width = $reader->uint(2, Endianness::Little);
        $height = $reader->uint(2, Endianness::Little);
        $packed = $reader->uint(1);

        return [
            'left'    => $left,
            'top'     => $top,
            'width'   => $width,
            'height'  => $height,
            'packed'  => $packed,
            'lctflag' => ($packed & 0x80) >> 7,
            'iflag'   => ($packed & 0x40) >> 6,
            'sflag'   => ($packed & 0x20) >> 5,
            'lctsize' => ($packed & 0x07),
        ];
    }

    /**
     * Get the size of a color table
     */
    private function getColorTableSize(int $ctsize): int
    {
        return 3 * 2 ** ($ctsize + 1);
    }
}
