<?php

namespace Formwork\Images\Decoder;

use Formwork\Images\Binary\ByteReader;
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

        $reader = new ByteReader($data);

        while ($reader->remaining() > 0) {
            $offset = $reader->position();

            $size = $reader->uint(4);
            $type = $reader->bytes(4);
            $headerSize = 8;

            if ($size === 1) {
                $size = $reader->uint(8);
                $headerSize = 16;
            } elseif ($size === 0) {
                // The box extends to the end of the file
                $size = strlen($data) - $offset;
            }

            if ($size < $headerSize) {
                throw new UnexpectedValueException('Invalid box size');
            }

            $value = $reader->bytes($size - $headerSize);

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
