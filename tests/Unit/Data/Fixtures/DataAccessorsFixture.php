<?php

namespace Formwork\Tests\Unit\Data\Fixtures;

use Formwork\Data\Attributes\Getter;
use Formwork\Data\Attributes\Setter;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataGetter;
use Formwork\Data\Traits\DataSetter;

/**
 * Data backed by properties and methods declared as getters and setters
 */
class DataAccessorsFixture implements Arrayable
{
    use DataArrayable;
    use DataGetter;
    use DataSetter;

    #[Getter]
    #[Setter]
    protected ?string $title = null;

    #[Getter(key: 'label')]
    protected string $name = 'Name';

    #[Getter(export: false)]
    protected string $secret = 'Secret';

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    #[Getter]
    public function getSlug(): string
    {
        return 'slug';
    }

    #[Getter]
    public function isPublished(): bool
    {
        return true;
    }

    #[Getter]
    public function issue(): string
    {
        return 'issue';
    }

    #[Setter]
    public function setColor(string $color): void
    {
        $this->data['color'] = strtoupper($color);
    }

    #[Setter]
    public function tone(string $tone): void
    {
        $this->data['tone'] = strtolower($tone);
    }

    #[Setter(key: 'size')]
    public function resize(string $size): void
    {
        $this->data['size'] = "{$size}px";
    }

    /**
     * @return array<string, string>
     */
    public function data(): array
    {
        return $this->data;
    }
}
