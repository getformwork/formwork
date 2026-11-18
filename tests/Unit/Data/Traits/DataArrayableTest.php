<?php

namespace Formwork\Tests\Unit\Data\Traits;

use Formwork\Data\Traits\DataArrayable;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Data\Fixtures\DataAccessorsFixture;
use Formwork\Tests\Unit\Data\Fixtures\DataArrayableFixture;
use PHPUnit\Framework\Attributes\CoversTrait;

#[CoversTrait(DataArrayable::class)]
final class DataArrayableTest extends TestCase
{
    public function testToArrayReturnsTheData(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $dataArrayableFixture = new DataArrayableFixture($data);
        $this->assertSame($data, $dataArrayableFixture->toArray());
    }

    public function testExportedGettersTakePrecedenceOverTheData(): void
    {
        $dataAccessorsFixture = new DataAccessorsFixture(['label' => 'Ignored', 'other' => 'Other']);

        $this->assertSame([
            'title'     => null,
            'label'     => 'Name',
            'slug'      => 'slug',
            'published' => true,
            'issue'     => 'issue',
            'other'     => 'Other',
        ], $dataAccessorsFixture->toArray());
    }
}
