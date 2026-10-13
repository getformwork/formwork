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
        $first = LazySingleton::instance();
        $first->counter = 5;

        $this->assertInstanceOf(LazySingleton::class, $first);
        $this->assertSame($first, LazySingleton::instance());
        $this->assertSame(5, LazySingleton::instance()->counter);
    }

    #[RunInSeparateProcess]
    public function testSingletonsCannotBeCloned(): void
    {
        $instance = LazySingleton::instance();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot clone ' . LazySingleton::class . ', the class is a singleton');
        clone $instance;
    }

    #[RunInSeparateProcess]
    public function testSingletonsRegisteredByTheConstructorBecomeTheInstance(): void
    {
        $created = new EagerSingleton();

        $this->assertSame($created, EagerSingleton::instance());
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
        $derived = DerivedSingleton::instance();
        $base = BaseSingleton::instance();

        $this->assertInstanceOf(DerivedSingleton::class, $derived);
        $this->assertNotInstanceOf(DerivedSingleton::class, $base, 'The base class instance must not be an instance of its subclass');
        $this->assertSame(BaseSingleton::class, $base::class);
    }

    #[RunInSeparateProcess]
    public function testBaseInstanceIsNotReturnedForSubclasses(): void
    {
        $base = BaseSingleton::instance();

        $this->assertSame(DerivedSingleton::class, DerivedSingleton::instance()::class);
        $this->assertSame(BaseSingleton::class, $base::class);
    }
}
