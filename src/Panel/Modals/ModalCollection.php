<?php

namespace Formwork\Panel\Modals;

use Formwork\Data\AbstractCollection;

/**
 * @extends AbstractCollection<Modal>
 */
class ModalCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Modal::class;

    protected bool $mutable = true;
}
