<?php

namespace Formwork\Images\Decoder;

use Generator;
use InvalidArgumentException;
use UnexpectedValueException;

final class PngDecoder implements DecoderInterface
{
    /**
     * PNG file header
     */
    private const string PNG_HEADER = "\x89PNG\x0d\x0a\x1a\x0a";

    public function decode(string &$data): Generator
    {
        if (!str_starts_with($data, self::PNG_HEADER)) {
            throw new InvalidArgumentException('Invalid PNG data');
        }

        $position = strlen(self::PNG_HEADER);

        while ($position < strlen($data)) {
            $offset = $position;
            $size = $this->unpack('N', $data, $position)[1];
            $position += 4;
            $type = substr($data, $position, 4);
            $position += $size + 4;
            $checksum = $this->unpack('N', $data, $position)[1];
            $position += 4;

            yield [
                'offset'   => $offset,
                'size'     => $size,
                'type'     => $type,
                'value'    => substr($data, $offset + 8, $size),
                'checksum' => $checksum,
                'position' => &$position,
            ];
        }
    }

    /**
     * @return array<int|string, mixed>
     */
    private function unpack(string $format, string $string, int $offset = 0): array
    {
        return unpack($format, $string, $offset) ?: throw new UnexpectedValueException('Cannot unpack string');
    }
}
