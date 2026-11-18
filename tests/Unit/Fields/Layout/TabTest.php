<?php

namespace Formwork\Tests\Unit\Fields\Layout;

use Formwork\Fields\Layout\Tab;
use Formwork\Fields\Translations\Translations;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;

#[CoversClass(Tab::class)]
#[CoversTrait(Translations::class)]
final class TabTest extends TestCase
{
    use ProvidesLayoutItemTests;

    protected function layoutItem(array $data): Tab
    {
        return new Tab($data);
    }
}
