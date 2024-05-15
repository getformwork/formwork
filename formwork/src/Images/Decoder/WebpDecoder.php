<?php

namespace Formwork\Images\Decoder;

use Generator;
use InvalidArgumentException;
use UnexpectedValueException;

class WebpDecoder implements DecoderInterface
{
    protected const RIFF_HEADER = 'RIFF';

    protected const WEBP_HEADER = 'WEBP';

    public function decode(string &$data): Generator
    {
        if (!str_starts_with($data, self::RIFF_HEADER)) {
            throw new InvalidArgumentException('Invalid WEBP data');
        }

        $position = strlen(self::RIFF_HEADER) + 4;

        if (strpos($data, self::WEBP_HEADER, $position) !== $position) {
            throw new InvalidArgumentException('Invalid WEBP data');
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
