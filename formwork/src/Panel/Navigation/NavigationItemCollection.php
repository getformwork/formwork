<?php

namespace Formwork\Panel\Navigation;

use Formwork\Data\AbstractCollection;
use Formwork\Data\Contracts\ArraySerializable;
use Formwork\Utils\Arr;

/**
 * @extends AbstractCollection<NavigationItem>
 */
class NavigationItemCollection extends AbstractCollection implements ArraySerializable
{
    protected bool $associative = true;

    protected ?string $dataType = NavigationItem::class;

    protected bool $mutable = true;

    public function toArray(): array
    {
        /** @var array<string, NavigationItem> */
        return Arr::map($this->data, fn(NavigationItem $navigationItem) => $navigationItem->toArray());
    }

    public static function fromArray(array $data): self
    {
        return new self(Arr::map($data, fn(array $item, string $id) => NavigationItem::fromArray([...$item, 'id' => $id])));
    }
}
