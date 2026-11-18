<?php

namespace Formwork\Tests\Unit\Fields\Layout;

use Formwork\Fields\Layout\Section;
use Formwork\Fields\Layout\SectionCollection;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SectionCollection::class)]
final class SectionCollectionTest extends TestCase
{
    use ProvidesLayoutCollectionTests;

    protected function layoutCollection(array $data): SectionCollection
    {
        return new SectionCollection($data);
    }

    protected function layoutItemClass(): string
    {
        return Section::class;
    }
}
