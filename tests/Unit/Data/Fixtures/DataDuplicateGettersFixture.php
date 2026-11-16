<?php

namespace Formwork\Tests\Unit\Data\Fixtures;

use Formwork\Data\Attributes\Getter;
use Formwork\Data\Traits\DataGetter;

/**
 * Data with two getters of the same key
 */
class DataDuplicateGettersFixture
{
    use DataGetter;

    #[Getter]
    protected string $slug = 'slug';

    #[Getter]
    public function getSlug(): string
    {
        return $this->slug;
    }
}
