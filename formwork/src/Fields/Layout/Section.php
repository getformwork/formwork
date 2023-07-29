<?php

namespace Formwork\Fields\Layout;

use Formwork\App;
use Formwork\Data\Traits\DataGetter;
use Formwork\Utils\Str;

class Section
{
    use DataGetter;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get a value by key and return whether it is equal to boolean `true`
     */
    public function is(string $key, bool $default = false): bool
    {
        return $this->get($key, $default) === true;
    }

    /**
     * Get field label
     */
    public function label(): string
    {
        $translation = App::instance()->translations()->getCurrent();
        return Str::interpolate($this->get('label', ''), fn ($key) => $translation->translate($key));
    }
}
