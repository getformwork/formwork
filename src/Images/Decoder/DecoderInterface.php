<?php

namespace Formwork\Images\Decoder;

use Generator;

interface DecoderInterface
{
    /**
     * Decode image data
     *
     * @return Generator<int, array<string, mixed>>
     */
    public function decode(string &$data): Generator;
}
