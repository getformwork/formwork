<?php

namespace Formwork\Images\Decoder;

use Generator;
use InvalidArgumentException;
use UnexpectedValueException;

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

    public function decode(string &$data): Generator
    {
        if (!str_starts_with($data, self::RIFF_HEADER)) {
            throw new InvalidArgumentException('Invalid WebP data');
        }

        $position = strlen(self::RIFF_HEADER) + 4;

        if (strpos($data, self::WEBP_HEADER, $position) !== $position) {
            throw new InvalidArgumentException('Invalid WebP data');
        }

        $position += strlen(self::WEBP_HEADER);

        while ($position < strlen($data)) {
            $offset = $position;
            $type = substr($data, $position, 4);
            $position += 4;
            $size = $this->unpack('V', $data, $position)[1];

            if ($size % 2 !== 0) {
                $size++;
            }

            $position += $size + 4;

            yield [
                'offset'   => $offset,
                'size'     => $size,
                'type'     => $type,
                'value'    => substr($data, $offset + 8, $size),
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
