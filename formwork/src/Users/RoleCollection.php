<?php

namespace Formwork\Users;

use Formwork\Data\AbstractCollection;

class RoleCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Role::class;

    protected bool $mutable = true;
}
