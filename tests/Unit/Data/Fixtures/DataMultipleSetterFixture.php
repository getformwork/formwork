<?php

namespace Formwork\Tests\Unit\Data\Fixtures;

use Formwork\Data\Traits\DataMultipleSetter;

class DataMultipleSetterFixture
{
    use DataMultipleSetter;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @return array<string, string>
     */
    public function data(): array
    {
        return $this->data;
    }
}
