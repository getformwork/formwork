<?php

namespace Formwork\Tests\Unit\Model\Fixtures;

use Formwork\Data\Attributes\Getter;

class UninitializedModelFixture extends ModelFixture
{
    #[Getter]
    public string $uninitialized;
}
