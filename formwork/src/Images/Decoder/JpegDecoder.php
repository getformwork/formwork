<?php

namespace Formwork\Images\Decoder;

use Formwork\Images\Binary\Bytes;
use Generator;
use InvalidArgumentException;
use UnexpectedValueException;

final class JpegDecoder implements DecoderInterface
{
    public function decode(string &$data): Generator
    {
        $length = strlen($data);

        if (!str_starts_with($data, "\xff\xd8")) {
            throw new InvalidArgumentException('Invalid JPEG data');
        }

        $position = 0;

        while ($position < $length) {
            $position = strpos($data, "\xff", $position);

            if ($position === false) {
                throw new InvalidArgumentException('Invalid JPEG data');
            }

            $offset = $position;
            $position++;

            // Any number of 0xff bytes can pad a marker
            while ($position < $length && $data[$position] === "\xff") {
                $position++;
            }

            if ($position >= $length) {
                throw new UnexpectedValueException('Unexpected end of data');
            }

            $type = ord($data[$position]);
            $position++;

            // A stuffed zero byte is not a marker
            if ($type === 0x00) {
                continue;
            }

            if ($this->hasNoLength($type)) {
                $size = 0;
                $value = null;
            } else {
                $size = Bytes::unpack($data, $position, 2);

                if ($size < 2 || $position + $size > $length) {
                    throw new UnexpectedValueException('Invalid segment size');
                }

                $value = substr($data, $position + 2, $size - 2);

                // The entropy-coded data following the header of a scan belongs to the segment
                if ($type === 0xda) {
                    $end = $this->seekSegmentEnd($data, $position + $size);
                    $value .= substr($data, $position + $size, $end - $position - $size);
                    $size = $end - $position;
                }
            }

            $position += $size;

            yield [
                'offset'   => $offset,
                'size'     => $size,
                'type'     => $type,
                'value'    => $value,
                'position' => $position,
            ];

            if ($type === 0xd9) {
                return;
            }
        }
    }

    /**
     * Whether a marker is not followed by a length field
     */
    private function hasNoLength(int $type): bool
    {
        return $type === 0x01 || ($type >= 0xd0 && $type <= 0xd9);
    }

    /**
     * Seek the end of a segment
     */
    private function seekSegmentEnd(string &$data, int $position): int
    {
        $length = strlen($data);

        while ($position < $length) {
            $position = strpos($data, "\xff", $position);

            if ($position === false) {
                break;
            }

            $position++;

            if ($position >= $length) {
                break;
            }

            $type = ord($data[$position]);

            // Stuffed zero bytes and restart markers are part of the scan
            if ($type !== 0x00 && ($type < 0xd0 || $type > 0xd7)) {
                return $position - 1;
            }
        }

        throw new UnexpectedValueException('Segment end not found');
    }
}
