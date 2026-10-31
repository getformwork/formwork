<?php

namespace Formwork\Images\Isobmff;

/**
 * Area of a file in which the data of items is stored one after the other
 *
 * @since 2.4.0
 */
final class DataArea
{
    private string $payload = '';

    /**
     * Append the data of an item and get the extents locating it, relative to the start of the area
     *
     * @return list<Extent>
     */
    public function place(string $data): array
    {
        $extents = $data === '' ? [] : [new Extent(strlen($this->payload), strlen($data))];
        $this->payload .= $data;

        return $extents;
    }

    public function payload(): string
    {
        return $this->payload;
    }
}
