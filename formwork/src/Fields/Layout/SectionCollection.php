<?php

namespace Formwork\Fields\Layout;

use Formwork\Data\AbstractCollection;
use Formwork\Utils\Arr;

class SectionCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Section::class;

    /**
     * @param array<string, Section> $sections
     */
    public function __construct(array $sections)
    {
        parent::__construct(Arr::map($sections, fn ($section) => new Section($section)));
    }
}
