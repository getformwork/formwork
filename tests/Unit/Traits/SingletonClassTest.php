<?php

namespace Formwork\Tests\Unit\Traits;

use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Traits\Fixtures\BaseSingletonFixture;
use Formwork\Tests\Unit\Traits\Fixtures\DerivedSingletonFixture;
use Formwork\Tests\Unit\Traits\Fixtures\EagerSingletonFixture;
use Formwork\Tests\Unit\Traits\Fixtures\LazySingletonFixture;
use Formwork\Tests\Unit\Traits\Fixtures\OtherLazySingletonFixture;
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
        $lazySingleton = LazySingletonFixture::instance();
        $lazySingleton->counter = 5;

        $this->assertInstanceOf(LazySingletonFixture::class, $lazySingleton);
        $this->assertSame($lazySingleton, LazySingletonFixture::instance());
        $this->assertSame(5, LazySingletonFixture::instance()->counter);
    }

    #[RunInSeparateProcess]
    public function testSingletonsCannotBeCloned(): void
    {
        $instance = LazySingletonFixture::instance();

        $logicException = $this->captureException(LogicException::class, fn() => clone $instance);

        $this->assertSame('Cannot clone ' . LazySingletonFixture::class . ', the class is a singleton', $logicException->getMessage());
    }

    #[RunInSeparateProcess]
    public function testSingletonsRegisteredByTheConstructorBecomeTheInstance(): void
    {
        $eagerSingleton = new EagerSingletonFixture();

        $this->assertSame($eagerSingleton, EagerSingletonFixture::instance());
    }

    #[RunInSeparateProcess]
    public function testSingletonsRegisteredByTheConstructorCannotBeCreatedTwice(): void
    {
        new EagerSingletonFixture();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('the class is a singleton and cannot be instiantated again');
        new EagerSingletonFixture();
    }

    #[RunInSeparateProcess]
    public function testSingletonsRegisteredByTheConstructorCannotBeCreatedAfterInstance(): void
    {
        EagerSingletonFixture::instance();

        $this->expectException(LogicException::class);
        new EagerSingletonFixture();
    }

    #[RunInSeparateProcess]
    public function testEachClassHasItsOwnInstance(): void
    {
        $this->assertNotSame(LazySingletonFixture::instance(), OtherLazySingletonFixture::instance());
        $this->assertInstanceOf(OtherLazySingletonFixture::class, OtherLazySingletonFixture::instance());
    }

    #[RunInSeparateProcess]
    public function testSubclassesGetTheirOwnInstance(): void
    {
        $derivedSingleton = DerivedSingletonFixture::instance();
        $baseSingleton = BaseSingletonFixture::instance();

        $this->assertInstanceOf(DerivedSingletonFixture::class, $derivedSingleton);
        $this->assertNotInstanceOf(DerivedSingletonFixture::class, $baseSingleton, 'The base class instance must not be an instance of its subclass');
        $this->assertSame(BaseSingletonFixture::class, $baseSingleton::class);
    }

    #[RunInSeparateProcess]
    public function testBaseInstanceIsNotReturnedForSubclasses(): void
    {
        $baseSingleton = BaseSingletonFixture::instance();

        $this->assertSame(DerivedSingletonFixture::class, DerivedSingletonFixture::instance()::class);
        $this->assertSame(BaseSingletonFixture::class, $baseSingleton::class);
    }
}
