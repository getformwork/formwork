<?php

namespace Formwork\Data\Traits;

/**
 * @template TData of array<int|string, mixed> = array<int|string, mixed>
 */
trait DataCountableIterator
{
    /** @use DataCountable<TData> */
    use DataCountable;

    /** @use DataIterator<TData> */
    use DataIterator;
}
