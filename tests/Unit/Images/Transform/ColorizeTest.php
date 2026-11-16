<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\Colorize;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Colorize::class)]
final class ColorizeTest extends TestCase
{
    public function testColorIsAddedToEachPixel(): void
    {
        $gdImage = imagecreatetruecolor(1, 1);
        imagesetpixel($gdImage, 0, 0, 0x804020);

        $gdImage = (new Colorize(10, 20, 30, 0))->apply($gdImage, new ImageInfo(['hasAlphaChannel' => false]));

        $this->assertSame(0x8A543E, imagecolorat($gdImage, 0, 0));
    }

    #[DataProvider('invalidColorProvider')]
    public function testColorComponentsMustBeInRange(int $red, int $green, int $blue, int $alpha, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        new Colorize($red, $green, $blue, $alpha);
    }

    /**
     * @return iterable<string, array{int, int, int, int, string}>
     */
    public static function invalidColorProvider(): iterable
    {
        yield 'red' => [256, 0, 0, 0, '$red value must be in range 0-255, 256 given'];
        yield 'green' => [0, -1, 0, 0, '$green value must be in range 0-255, -1 given'];
        yield 'blue' => [0, 0, 256, 0, '$blue value must be in range 0-255, 256 given'];
        yield 'alpha' => [0, 0, 0, 128, '$alpha value must be in range 0-127, 128 given'];
    }
}
