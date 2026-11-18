<?php

namespace Formwork\Tests\Unit\Fields\Layout;

use Formwork\Fields\Layout\Section;
use Formwork\Fields\Translations\Translations;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;

#[CoversClass(Section::class)]
#[CoversTrait(Translations::class)]
final class SectionTest extends TestCase
{
    use ProvidesLayoutItemTests;

    protected function layoutItem(array $data): Section
    {
        return new Section($data);
    }
}
