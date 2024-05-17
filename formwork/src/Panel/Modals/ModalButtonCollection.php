<?php

namespace Formwork\Panel\Modals;

use Formwork\Data\AbstractCollection;

class ModalButtonCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = ModalButton::class;
}
