<?php

namespace Formwork\Tests\Unit\Traits;

use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Traits\Fixtures\StaticUtilityFixture;
use Formwork\Traits\StaticClass;
use LogicException;
use PHPUnit\Framework\Attributes\CoversTrait;

#[CoversTrait(StaticClass::class)]
final class StaticClassTest extends TestCase
{
    public function testStaticClassesCannotBeInstantiated(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot construct ' . StaticUtilityFixture::class . ', the class is static');
        new StaticUtilityFixture();
    }

    public function testStaticMethodsStillWork(): void
    {
        $this->assertSame(8, StaticUtilityFixture::double(4));
    }
}
