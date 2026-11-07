<?php

namespace Formwork\Tests\Unit\Traits;

use BadMethodCallException;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Traits\Fixtures\ObjectWithMethodsFixture;
use Formwork\Traits\Methods;
use PHPUnit\Framework\Attributes\CoversTrait;
use RuntimeException;

#[CoversTrait(Methods::class)]
final class MethodsTest extends TestCase
{
    public function testObjectsStartWithoutMethods(): void
    {
        $objectWithMethods = new ObjectWithMethodsFixture();

        $this->assertSame([], $objectWithMethods->getMethods());
        $this->assertFalse($objectWithMethods->hasMethod('anything'));
    }

    public function testDefinedMethodsAreCalledThroughTheMagicCall(): void
    {
        $objectWithMethods = new ObjectWithMethodsFixture(['add' => static fn(int $a, int $b): int => $a + $b]);

        $this->assertTrue($objectWithMethods->hasMethod('add'));
        $this->assertSame(5, $objectWithMethods->add(2, 3));
    }

    public function testMethodsReceiveAllTheArguments(): void
    {
        $objectWithMethods = new ObjectWithMethodsFixture(['collect' => static fn(mixed ...$arguments): array => $arguments]);

        $this->assertSame([], $objectWithMethods->collect());
        $this->assertSame([1, 'two', null, [3]], $objectWithMethods->collect(1, 'two', null, [3]));
    }

    public function testMethodsCanReturnAnyValue(): void
    {
        $objectWithMethods = new ObjectWithMethodsFixture([
            'nothing' => static fn() => null,
            'false'   => static fn() => false,
            'zero'    => static fn() => 0,
        ]);

        $this->assertNull($objectWithMethods->nothing());
        $this->assertFalse($objectWithMethods->false());
        $this->assertSame(0, $objectWithMethods->zero());
    }

    public function testMethodsAreAvailableThroughGetMethods(): void
    {
        $closure = static fn(): string => 'value';
        $objectWithMethods = new ObjectWithMethodsFixture(['first' => $closure]);

        $this->assertSame(['first' => $closure], $objectWithMethods->getMethods());
    }

    public function testUndefinedMethodsThrowABadMethodCallException(): void
    {
        $objectWithMethods = new ObjectWithMethodsFixture(['defined' => static fn() => null]);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Call to undefined method ' . ObjectWithMethodsFixture::class . '::missing()');
        $objectWithMethods->missing();
    }

    public function testMethodNamesAreCaseSensitive(): void
    {
        $objectWithMethods = new ObjectWithMethodsFixture(['lower' => static fn(): string => 'ok']);

        $this->assertTrue($objectWithMethods->hasMethod('lower'));
        $this->assertFalse($objectWithMethods->hasMethod('LOWER'));
    }

    public function testExceptionsThrownByMethodsPropagate(): void
    {
        $objectWithMethods = new ObjectWithMethodsFixture(['fail' => static function (): never {
            throw new RuntimeException('method failed');
        }]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('method failed');
        $objectWithMethods->fail();
    }

    public function testCallMethodInvokesTheClosureDirectly(): void
    {
        $objectWithMethods = new ObjectWithMethodsFixture(['join' => static fn(string $a, string $b): string => $a . $b]);

        $this->assertSame('ab', $objectWithMethods->invoke('join', ['a', 'b']));
    }

    public function testNullMethodsAreConsideredUndefined(): void
    {
        $objectWithMethods = new ObjectWithMethodsFixture($this->unchecked(['nothing' => null]));

        $this->assertFalse($objectWithMethods->hasMethod('nothing'));
        $this->expectException(BadMethodCallException::class);
        $objectWithMethods->nothing();
    }
}
