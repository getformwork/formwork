<?php

namespace Formwork\Tests\Unit\Traits;

use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Traits\Fixtures\StaticUtilityFixture;
use Formwork\Traits\StaticClass;
use LogicException;
use PHPUnit\Framework\Attributes\CoversTrait;
use ReflectionMethod;

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

    public function testTheConstructorCannotBeOverridden(): void
    {
        $reflectionMethod = new ReflectionMethod(StaticUtilityFixture::class, '__construct');

        $this->assertTrue($reflectionMethod->isFinal());
        $this->assertTrue($reflectionMethod->isPublic());
    }
}
