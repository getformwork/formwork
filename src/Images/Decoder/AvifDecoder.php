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

    public function decode(string &$data): Generator
    {
        if (strlen($data) < 12) {
            throw new InvalidArgumentException('Invalid AVIF data');
        }

        $position = 0;
        $this->unpack('N', $data, $position);
        $boxType = substr($data, 4, 4);

        if ($boxType !== self::FTYP_BOX) {
            throw new InvalidArgumentException('Invalid AVIF data: missing ftyp box');
        }

        $majorBrand = substr($data, 8, 4);
        if (!in_array($majorBrand, self::VALID_BRANDS, true)) {
            throw new InvalidArgumentException('Invalid AVIF data: unsupported brand');
        }

        $position = 0;

        while ($position < strlen($data)) {
            $offset = $position;

            $size = $this->unpack('N', $data, $position)[1];
            $headerSize = 8;

            if ($size === 1) {
                $size = $this->unpack('J', $data, $position + 8)[1];
                $headerSize = 16;
            } elseif ($size === 0) {
                $size = strlen($data) - $position;
            }

            $type = substr($data, $position + 4, 4);
            $contentSize = $size - $headerSize;
            $position += $size;

            yield [
                'offset'   => $offset,
                'size'     => $size,
                'type'     => $type,
                'value'    => substr($data, $offset + $headerSize, $contentSize),
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
