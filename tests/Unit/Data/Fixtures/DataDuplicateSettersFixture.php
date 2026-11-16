<?php

namespace Formwork\Tests\Unit\Data\Fixtures;

use Formwork\Data\Attributes\Setter;
use Formwork\Data\Traits\DataSetter;

/**
 * Data with two setters of the same key
 */
class DataDuplicateSettersFixture
{
    use DataSetter;

    #[Setter]
    protected string $slug = 'slug';

    #[Setter]
    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }
}
