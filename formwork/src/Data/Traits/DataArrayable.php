<?php

namespace Formwork\Data\Traits;

use Formwork\Data\Contracts\Arrayable;

/**
 * @phpstan-require-implements Arrayable
 */
trait DataArrayable
{
    use DataAccessors;

    /**
     * @var array<mixed>
     */
    protected array $data = [];

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        $data = [];

        foreach ($this->dataGetters() as $key => $accessor) {
            if ($accessor['export']) {
                $data[$key] = match ($accessor['type']) {
                    'method'   => $this->{$accessor['name']}(),
                    'property' => $this->{$accessor['name']},
                };
            }
        }

        return $data + $this->data;
    }
}
