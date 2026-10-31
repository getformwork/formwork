<?php

namespace Formwork\Images\Decoder;

use Formwork\Images\Binary\ByteReader;
use Formwork\Images\Binary\Endianness;
use Generator;
use InvalidArgumentException;

final class WebpDecoder implements DecoderInterface
{
    /**
     * RIFF header
     */
    private const string RIFF_HEADER = 'RIFF';

    /**
     * WebP file header
     */
    private const string WEBP_HEADER = 'WEBP';

    /**
     * Size of the RIFF header, including the size field
     */
    private const int RIFF_HEADER_SIZE = 8;

    public function decode(string &$data): Generator
    {
        if (!str_starts_with($data, self::RIFF_HEADER) || substr($data, 8, 4) !== self::WEBP_HEADER) {
            throw new InvalidArgumentException('Invalid WebP data');
        }

        $reader = new ByteReader($data, 4);

        // Ignore any data after the end of the RIFF container
        $end = min(strlen($data), $reader->uint(4, Endianness::Little) + self::RIFF_HEADER_SIZE);

        $reader->skip(strlen(self::WEBP_HEADER));

        while ($reader->position() + self::RIFF_HEADER_SIZE <= $end) {
            $offset = $reader->position();
            $type = $reader->bytes(4);
            $size = $reader->uint(4, Endianness::Little);
            $value = $reader->bytes($size);

            // Chunks are padded to an even size, but the last padding byte may be missing
            if ($size % 2 !== 0 && $reader->remaining() > 0) {
                $reader->skip(1);
            }

            yield [
                'offset'   => $offset,
                'size'     => $size,
                'type'     => $type,
                'value'    => $value,
                'position' => $reader->position(),
            ];
        }
    }
}
