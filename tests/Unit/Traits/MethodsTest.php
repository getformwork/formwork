<?php

namespace Formwork\Tests\Unit\Traits;

use BadMethodCallException;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Traits\Fixtures\ObjectWithMethods;
use Formwork\Traits\Methods;
use PHPUnit\Framework\Attributes\CoversTrait;
use RuntimeException;

#[CoversTrait(Methods::class)]
final class MethodsTest extends TestCase
{
    public function testObjectsStartWithoutMethods(): void
    {
        $object = new ObjectWithMethods();

        $this->assertSame([], $object->getMethods());
        $this->assertFalse($object->hasMethod('anything'));
    }

    public function testDefinedMethodsAreCalledThroughTheMagicCall(): void
    {
        $object = new ObjectWithMethods(['add' => static fn(int $a, int $b): int => $a + $b]);

        $this->assertTrue($object->hasMethod('add'));
        $this->assertSame(5, $object->add(2, 3));
    }

    public function testMethodsReceiveAllTheArguments(): void
    {
        $object = new ObjectWithMethods(['collect' => static fn(mixed ...$arguments): array => $arguments]);

        $this->assertSame([], $object->collect());
        $this->assertSame([1, 'two', null, [3]], $object->collect(1, 'two', null, [3]));
    }

    public function testMethodsCanReturnAnyValue(): void
    {
        $object = new ObjectWithMethods([
            'nothing' => static fn() => null,
            'false'   => static fn() => false,
            'zero'    => static fn() => 0,
        ]);

        $this->assertNull($object->nothing());
        $this->assertFalse($object->false());
        $this->assertSame(0, $object->zero());
    }

    public function testMethodsAreAvailableThroughGetMethods(): void
    {
        $closure = static fn(): string => 'value';
        $object = new ObjectWithMethods(['first' => $closure]);

        $this->assertSame(['first' => $closure], $object->getMethods());
    }

    public function testUndefinedMethodsThrowABadMethodCallException(): void
    {
        $object = new ObjectWithMethods(['defined' => static fn() => null]);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Call to undefined method ' . ObjectWithMethods::class . '::missing()');
        $object->missing();
    }

    public function testMethodNamesAreCaseSensitive(): void
    {
        $object = new ObjectWithMethods(['lower' => static fn(): string => 'ok']);

        $this->assertTrue($object->hasMethod('lower'));
        $this->assertFalse($object->hasMethod('LOWER'));
    }

    public function testExceptionsThrownByMethodsPropagate(): void
    {
        $object = new ObjectWithMethods(['fail' => static function (): never {
            throw new RuntimeException('method failed');
        }]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('method failed');
        $object->fail();
    }

    public function testCallMethodInvokesTheClosureDirectly(): void
    {
        $object = new ObjectWithMethods(['join' => static fn(string $a, string $b): string => $a . $b]);

        $this->assertSame('ab', $object->invoke('join', ['a', 'b']));
    }

    public function testNullMethodsAreConsideredUndefined(): void
    {
        $object = new ObjectWithMethods(['nothing' => null]);

        $this->assertFalse($object->hasMethod('nothing'));
        $this->expectException(BadMethodCallException::class);
        $object->nothing();
    }
}
