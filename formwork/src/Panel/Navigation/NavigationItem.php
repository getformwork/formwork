<?php

namespace Formwork\Panel\Navigation;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;

class NavigationItem implements Arrayable
{
    use DataArrayable;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(protected string $id, array $data)
    {
        $this->data = $data;
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
        return $this->data['permissions'];
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
}
