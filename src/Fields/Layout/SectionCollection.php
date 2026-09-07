<?php

namespace Formwork\Fields\Layout;

use Formwork\Data\AbstractCollection;
use Formwork\Utils\Arr;

/**
 * @extends AbstractCollection<Section>
 */
class SectionCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Section::class;

    /**
     * @param array<string, array<string, mixed>> $sections
     */
    public function __construct(array $sections)
    {
        parent::__construct(Arr::map($sections, fn(array $section, string $name) => new Section(['name' => $name, ...$section])));
    }
}
