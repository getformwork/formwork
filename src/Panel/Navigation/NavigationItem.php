<?php

namespace Formwork\Panel\Navigation;

use Formwork\Data\Contracts\ArraySerializable;

class NavigationItem implements ArraySerializable
{
    /**
     * @var array<string,mixed>
     */
    protected array $data = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(protected string $id, array $data)
    {
        foreach ($data as $key => $value) {
            if ($key === 'children') {
                $value = NavigationItemCollection::fromArray($value);
            }
            $this->data[$key] = $value;
        }
    }

    /**
     * Get navigation item id
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get navigation item label
     */
    public function label(): string
    {
        return $this->data['label'];
    }

    /**
     * Get navigation item uri
     */
    public function uri(): string
    {
        return $this->data['uri'];
    }

    /**
     * Get navigation item permissions
     */
    public function permissions(): ?string
    {
        return $this->data['permissions'] ?? null;
    }

    /**
     * Get navigation item badge
     */
    public function badge(): ?string
    {
        return $this->data['badge'] ?? null;
    }

    /**
     * Get navigation item icon
     *
     * @since 2.2.0
     */
    public function icon(): ?string
    {
        return $this->data['icon'] ?? null;
    }

    /**
     * Get navigation item visibility
     *
     * @since 2.3.0
     */
    public function visible(): bool
    {
        return $this->data['visible'] ?? true;
    }

    /**
     * Get navigation item children
     */
    public function children(): ?NavigationItemCollection
    {
        return $this->data['children'] ?? null;
    }

    public function toArray(): array
    {
        $data = $this->data;
        if (isset($data['children'])) {
            $data['children'] = $data['children']->toArray();
        }
        return [...$data, 'id' => $this->id];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['id'], $data);
    }
}
