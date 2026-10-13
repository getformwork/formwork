<?php

namespace Formwork\Tests\Unit\Traits;

use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Traits\Fixtures\StaticUtility;
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
        $this->expectExceptionMessage('Cannot construct ' . StaticUtility::class . ', the class is static');
        new StaticUtility();
    }

    public function testStaticMethodsStillWork(): void
    {
        $this->assertSame(8, StaticUtility::double(4));
    }

    public function testTheConstructorCannotBeOverridden(): void
    {
        $constructor = new ReflectionMethod(StaticUtility::class, '__construct');

        $this->assertTrue($constructor->isFinal());
        $this->assertTrue($constructor->isPublic());
    }
}
