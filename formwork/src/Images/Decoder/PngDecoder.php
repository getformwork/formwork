<?php

namespace Formwork\Images\Decoder;

use Formwork\Images\Binary\ByteReader;
use Generator;
use InvalidArgumentException;

final class PngDecoder implements DecoderInterface
{
    /**
     * PNG file header
     */
    private const string PNG_HEADER = "\x89PNG\x0d\x0a\x1a\x0a";

    /**
     * Type of the last chunk of a PNG file
     */
    private const string END_CHUNK = 'IEND';

    public function decode(string &$data): Generator
    {
        if (!str_starts_with($data, self::PNG_HEADER)) {
            throw new InvalidArgumentException('Invalid PNG data');
        }

        $reader = new ByteReader($data, strlen(self::PNG_HEADER));

        while ($reader->remaining() > 0) {
            $offset = $reader->position();
            $size = $reader->uint(4);
            $type = $reader->bytes(4);
            $value = $reader->bytes($size);
            $checksum = $reader->uint(4);

            yield [
                'offset'   => $offset,
                'size'     => $size,
                'type'     => $type,
                'value'    => $value,
                'checksum' => $checksum,
                'position' => $reader->position(),
            ];

            if ($type === self::END_CHUNK) {
                return;
            }
        }
    }
}
