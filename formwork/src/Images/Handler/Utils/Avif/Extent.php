<?php

namespace Formwork\Images\Handler\Utils\Avif;

/**
 * Location of a chunk of item data described by an `iloc` box
 *
 * @since 2.3.0
 */
final class Extent
{
    /**
     * @param int      $index  Extent index (used by `iloc` version 1 and 2 only)
     * @param int      $offset Offset as stored in the `iloc` box
     * @param int      $length Length in bytes
     * @param Box|null $box    Box holding the data (if any), whose position in the file is not fixed
     * @param int      $anchor Offset of the data in the payload of $box
     */
    public function __construct(
        public int $index,
        public int $offset,
        public int $length,
        public ?Box $box = null,
        public int $anchor = 0,
    ) {}
}
