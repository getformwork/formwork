<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\Transform\Blur;
use Formwork\Images\Transform\BlurMode;
use Formwork\Images\Transform\Flip;
use Formwork\Images\Transform\FlipDirection;
use Formwork\Images\Transform\Resize;
use Formwork\Images\Transform\ResizeMode;
use Formwork\Images\Transform\TransformInterface;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Resize::class)]
#[CoversClass(Blur::class)]
#[CoversClass(Flip::class)]
final class TransformSerializationTest extends TestCase
{
    /**
     * @param class-string<TransformInterface> $class
     */
    #[DataProvider('transformProvider')]
    public function testTransformsCanBeRestoredFromTheirArrays(string $class, TransformInterface $transform): void
    {
        $restored = $class::fromArray($transform->toArray());

        $this->assertInstanceOf($class, $restored);
        $this->assertSame($transform->getSpecifier(), $restored->getSpecifier());
    }

    /**
     * @return iterable<string, array{class-string<TransformInterface>, TransformInterface}>
     */
    public static function transformProvider(): iterable
    {
        yield 'resize' => [Resize::class, new Resize(50, null, ResizeMode::Contain)];
        yield 'blur' => [Blur::class, new Blur(5, BlurMode::Gaussian)];
        yield 'flip' => [Flip::class, new Flip(FlipDirection::Vertical)];
    }

    /**
     * @param class-string<TransformInterface> $class
     * @param array<string, mixed>             $data
     */
    #[DataProvider('legacyArrayProvider')]
    public function testTransformsCanBeRestoredFromArraysWithTheFormerKeys(string $class, array $data, TransformInterface $expected): void
    {
        $restored = $class::fromArray($data);

        $this->assertInstanceOf($class, $restored);
        $this->assertSame($expected->getSpecifier(), $restored->getSpecifier());
    }

    /**
     * @return iterable<string, array{class-string<TransformInterface>, array<string, mixed>, TransformInterface}>
     */
    public static function legacyArrayProvider(): iterable
    {
        yield 'resize' => [Resize::class, ['width' => 50, 'height' => null, 'mode' => ResizeMode::Contain], new Resize(50, null, ResizeMode::Contain)];
        yield 'blur' => [Blur::class, ['amount' => 5, 'mode' => BlurMode::Gaussian], new Blur(5, BlurMode::Gaussian)];
        yield 'flip' => [Flip::class, ['direction' => FlipDirection::Vertical], new Flip(FlipDirection::Vertical)];
    }
}
