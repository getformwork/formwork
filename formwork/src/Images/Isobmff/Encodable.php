<?php

namespace Formwork\Images\Isobmff;

/**
 * @since 2.4.0
 */
interface Encodable
{
    /**
     * Convert the object to a generic box
     */
    public function toBox(): Box;
}
