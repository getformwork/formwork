<?php

namespace Formwork\Tests\Unit\Fields\Layout;

use Formwork\Fields\Layout\Tab;
use Formwork\Fields\Layout\TabCollection;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TabCollection::class)]
final class TabCollectionTest extends TestCase
{
    use ProvidesLayoutCollectionTests;

    protected function layoutCollection(array $data): TabCollection
    {
        return new TabCollection($data);
    }

    protected function layoutItemClass(): string
    {
        return Tab::class;
    }
}
