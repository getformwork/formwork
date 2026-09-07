<?php

namespace Formwork\Fields\Layout;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataGetter;
use Formwork\Fields\Translations\Translations;

class Section implements Arrayable
{
    use DataGetter;
    use DataArrayable;
    use Translations;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        array $data,
    ) {
        $this->data = ['order' => PHP_INT_MAX, ...$data];
    }

    /**
     * Get section name
     */
    public function name(): ?string
    {
        return $this->get('name');
    }

    /**
     * Get a value by key and return whether it is equal to boolean `true`
     */
    public function is(string $key, bool $default = false): bool
    {
        return $this->get($key, $default) === true;
    }

    /**
     * Get section label
     */
    public function label(): ?string
    {
        return $this->translate($this->get('label', $this->name()));
    }

    /**
     * Get section order
     *
     * @since 2.2.0
     */
    public function order(): int
    {
        return $this->get('order');
    }
}
