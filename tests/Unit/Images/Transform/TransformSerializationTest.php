<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\Transform\Blur;
use Formwork\Images\Transform\BlurMode;
use Formwork\Images\Transform\Brightness;
use Formwork\Images\Transform\Colorize;
use Formwork\Images\Transform\Contrast;
use Formwork\Images\Transform\Crop;
use Formwork\Images\Transform\Desaturate;
use Formwork\Images\Transform\EdgeDetect;
use Formwork\Images\Transform\Emboss;
use Formwork\Images\Transform\Flip;
use Formwork\Images\Transform\FlipDirection;
use Formwork\Images\Transform\Invert;
use Formwork\Images\Transform\Pixelate;
use Formwork\Images\Transform\Resize;
use Formwork\Images\Transform\ResizeMode;
use Formwork\Images\Transform\Rotate;
use Formwork\Images\Transform\Scale;
use Formwork\Images\Transform\Sharpen;
use Formwork\Images\Transform\Smoothen;
use Formwork\Images\Transform\TransformInterface;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Resize::class)]
#[CoversClass(Blur::class)]
#[CoversClass(Flip::class)]
#[CoversClass(Brightness::class)]
#[CoversClass(Colorize::class)]
#[CoversClass(Contrast::class)]
#[CoversClass(Crop::class)]
#[CoversClass(Desaturate::class)]
#[CoversClass(EdgeDetect::class)]
#[CoversClass(Emboss::class)]
#[CoversClass(Invert::class)]
#[CoversClass(Pixelate::class)]
#[CoversClass(Rotate::class)]
#[CoversClass(Scale::class)]
#[CoversClass(Sharpen::class)]
#[CoversClass(Smoothen::class)]
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
        yield 'brightness' => [Brightness::class, new Brightness(-20)];
        yield 'colorize' => [Colorize::class, new Colorize(10, 20, 30, 40)];
        yield 'contrast' => [Contrast::class, new Contrast(30)];
        yield 'crop' => [Crop::class, new Crop(1, 2, 3, 4)];
        yield 'desaturate' => [Desaturate::class, new Desaturate()];
        yield 'edge detect' => [EdgeDetect::class, new EdgeDetect()];
        yield 'emboss' => [Emboss::class, new Emboss()];
        yield 'invert' => [Invert::class, new Invert()];
        yield 'pixelate' => [Pixelate::class, new Pixelate(8)];
        yield 'rotate' => [Rotate::class, new Rotate(22.5)];
        yield 'scale' => [Scale::class, new Scale(0.5)];
        yield 'sharpen' => [Sharpen::class, new Sharpen()];
        yield 'smoothen' => [Smoothen::class, new Smoothen()];
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
