<?php

namespace Formwork\Tests\Unit\Traits;

use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Traits\Fixtures\BaseSingleton;
use Formwork\Tests\Unit\Traits\Fixtures\DerivedSingleton;
use Formwork\Tests\Unit\Traits\Fixtures\EagerSingleton;
use Formwork\Tests\Unit\Traits\Fixtures\LazySingleton;
use Formwork\Tests\Unit\Traits\Fixtures\OtherLazySingleton;
use Formwork\Traits\SingletonClass;
use LogicException;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

#[CoversTrait(SingletonClass::class)]
final class SingletonClassTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testInstanceIsCreatedOnFirstUseAndShared(): void
    {
        $lazySingleton = LazySingleton::instance();
        $lazySingleton->counter = 5;

        $this->assertInstanceOf(LazySingleton::class, $lazySingleton);
        $this->assertSame($lazySingleton, LazySingleton::instance());
        $this->assertSame(5, LazySingleton::instance()->counter);
    }

    #[RunInSeparateProcess]
    public function testSingletonsCannotBeCloned(): void
    {
        $instance = LazySingleton::instance();

        $logicException = $this->captureException(LogicException::class, fn() => clone $instance);

        $this->assertSame('Cannot clone ' . LazySingleton::class . ', the class is a singleton', $logicException->getMessage());
    }

    #[RunInSeparateProcess]
    public function testSingletonsRegisteredByTheConstructorBecomeTheInstance(): void
    {
        $eagerSingleton = new EagerSingleton();

        $this->assertSame($eagerSingleton, EagerSingleton::instance());
    }

    #[RunInSeparateProcess]
    public function testSingletonsRegisteredByTheConstructorCannotBeCreatedTwice(): void
    {
        new EagerSingleton();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('the class is a singleton and cannot be instiantated again');
        new EagerSingleton();
    }

    #[RunInSeparateProcess]
    public function testSingletonsRegisteredByTheConstructorCannotBeCreatedAfterInstance(): void
    {
        EagerSingleton::instance();

        $this->expectException(LogicException::class);
        new EagerSingleton();
    }

    #[RunInSeparateProcess]
    public function testEachClassHasItsOwnInstance(): void
    {
        $this->assertNotSame(LazySingleton::instance(), OtherLazySingleton::instance());
        $this->assertInstanceOf(OtherLazySingleton::class, OtherLazySingleton::instance());
    }

    #[RunInSeparateProcess]
    public function testSubclassesGetTheirOwnInstance(): void
    {
        $derivedSingleton = DerivedSingleton::instance();
        $baseSingleton = BaseSingleton::instance();

        $this->assertInstanceOf(DerivedSingleton::class, $derivedSingleton);
        $this->assertNotInstanceOf(DerivedSingleton::class, $baseSingleton, 'The base class instance must not be an instance of its subclass');
        $this->assertSame(BaseSingleton::class, $baseSingleton::class);
    }

    #[RunInSeparateProcess]
    public function testBaseInstanceIsNotReturnedForSubclasses(): void
    {
        $baseSingleton = BaseSingleton::instance();

        $this->assertSame(DerivedSingleton::class, DerivedSingleton::instance()::class);
        $this->assertSame(BaseSingleton::class, $baseSingleton::class);
    }
}
