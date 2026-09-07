<?php

namespace Formwork\Images\Decoder;

use Generator;
use InvalidArgumentException;
use UnexpectedValueException;

final class JpegDecoder implements DecoderInterface
{
    public function decode(string &$data): Generator
    {
        $position = 0;

        while ($position < strlen($data)) {
            $position = strpos($data, "\xff", $position);

            if ($position === false) {
                throw new InvalidArgumentException('Invalid JPEG data');
            }

            $offset = $position;
            $position += 1;
            $type = ord($data[$position]);
            $position += 1;

            if (($type > 0x00 && $type < 0xd0) || ($type > 0xda && $type < 0xff)) {
                $size = $this->unpack('n', $data, $position)[1];
            } elseif ($type === 0xda) {
                $size = $this->seekSegmentEnd($data, $position) - $position;
            } else {
                $size = 0;
            }

            $position += $size;

            yield [
                'offset'   => $offset,
                'size'     => $size,
                'type'     => $type,
                'value'    => $size > 0 ? substr($data, $offset + 4, $size - 2) : null,
                'position' => &$position,
            ];
        }
    }

    /**
     * Seek the end of a segment
     */
    private function seekSegmentEnd(string &$data, int $position): int
    {
        while ($position < strlen($data)) {
            $position = strpos($data, "\xff", $position);
            if ($position === false) {
                throw new InvalidArgumentException('Invalid JPEG data');
            }
            $position += 1;
            $type = ord($data[$position]);
            if (($type > 0x00 && $type < 0xd0) || $type > 0xd7) {
                return $position - 1;
            }
        }
        throw new UnexpectedValueException('Segment end not found');
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
