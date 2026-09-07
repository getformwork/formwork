<?php

namespace Formwork\Images\Decoder;

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

    public function decode(string &$data): Generator
    {
        if (!in_array(substr($data, 0, 6), self::GIF_HEADERS, true)) {
            throw new InvalidArgumentException('Invalid GIF data');
        }

        $position = 6;

        $desc = null;
        $label = null;

        while ($position < strlen($data)) {
            $offset = $position;

            switch (true) {
                case $offset === 6:
                    $type = 'LSD';
                    $size = 7;
                    $value = substr($data, $offset, 7);
                    $desc = $this->parseLogicalScreenDescriptor($value);
                    $position += $size;
                    break;

                case $offset === 13 && ($desc['gctflag'] ?? 0) === 1:
                    $type = 'GCT';
                    $size = $this->getColorTableSize($desc['gctsize']);
                    $value = substr($data, $offset, $size);
                    $position += $size;
                    break;

                default:
                    $separator = $data[$position];
                    $position++;

                    $desc = null;
                    $label = null;

                    switch ($separator) {
                        case ',':
                            $type = 'IMG';
                            $desc = $this->parseImageDescriptor(substr($data, $position, 9));
                            $position += 9;
                            if ($desc['lctflag'] === 1) {
                                $lctSize = $this->getColorTableSize($desc['lctsize']);
                                $position += $lctSize;
                            }
                            $desc['lzwsize'] = ord($data[$position]);
                            $position++;
                            $position = $this->seekBlockEnd($data, $position);
                            $size = $position - $offset + 1;
                            break;

                        case '!':
                            $type = 'EXT';
                            $label = ord($data[$position]);
                            $position++;
                            $position = $this->seekBlockEnd($data, $position);
                            $size = $position - $offset + 1;
                            break;

                        case ';':
                            $type = 'END';
                            $size = 0;
                            break;

                        default:
                            throw new UnexpectedValueException('Unexpected block introducer');
                    }

                    $position++;
            }

            yield [
                'offset'   => $offset,
                'size'     => $size,
                'type'     => $type,
                'label'    => $label,
                'desc'     => $desc,
                'value'    => substr($data, $offset, $size),
                'position' => &$position,
            ];
        }
    }

    /**
     * Seek the end of a block
     */
    private function seekBlockEnd(string &$data, int $position): int
    {
        while ($position < strlen($data)) {
            if ($data[$position] === "\x00") {
                return $position;
            }
            $size = ord($data[$position]);
            $position += $size + 1;
        }
        throw new UnexpectedValueException('Unexpected end of data');
    }

    /**
     * Parse logical screen descriptor
     *
     * @return array<string, int>
     */
    private function parseLogicalScreenDescriptor(string $data): array
    {
        return [
            'width'    => $this->unpack('v', $data, 0)[1],
            'height'   => $this->unpack('v', $data, 2)[1],
            'packed'   => ord($data[4]),
            'gctflag'  => (ord($data[4]) & 0x80) >> 7,
            'colorres' => (ord($data[4]) & 0x70) >> 4,
            'sflag'    => (ord($data[4]) & 0x08) >> 3,
            'gctsize'  => (ord($data[4]) & 0x07),
            'bgindex'  => ord($data[5]),
            'pxratio'  => ord($data[6]),
        ];
    }

    /**
     * Parse image descriptor
     *
     * @return array<string, int>
     */
    private function parseImageDescriptor(string $data): array
    {
        return [
            'left'    => $this->unpack('v', $data, 0)[1],
            'top'     => $this->unpack('v', $data, 2)[1],
            'width'   => $this->unpack('v', $data, 4)[1],
            'height'  => $this->unpack('v', $data, 6)[1],
            'packed'  => ord($data[8]),
            'lctflag' => (ord($data[8]) & 0x80) >> 7,
            'iflag'   => (ord($data[8]) & 0x40) >> 6,
            'sflag'   => (ord($data[8]) & 0x20) >> 5,
            'lctsize' => (ord($data[8]) & 0x07),
        ];
    }

    /**
     * Get the size of a color table
     */
    private function getColorTableSize(int $ctsize): int
    {
        return 3 * 2 ** ($ctsize + 1);
    }

    /**
     * Unpack data from a binary string
     *
     * @return array<int|string, mixed>
     */
    private function unpack(string $format, string $string, int $offset = 0): array
    {
        return unpack($format, $string, $offset) ?: throw new UnexpectedValueException('Cannot unpack string');
    }
}
