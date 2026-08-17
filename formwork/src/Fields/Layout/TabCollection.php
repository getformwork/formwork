<?php

namespace Formwork\Fields\Layout;

use Formwork\Data\AbstractCollection;
use Formwork\Utils\Arr;

/**
 * @extends AbstractCollection<Tab>
 */
class TabCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Tab::class;

    /**
     * @param array<string, array<string, mixed>> $tabs
     */
    public function __construct(array $tabs)
    {
        parent::__construct(Arr::map($tabs, fn(array $tab, string $name) => new Tab(['name' => $name, ...$tab])));
    }
}
