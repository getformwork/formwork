<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\Flip;
use Formwork\Images\Transform\FlipDirection;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Flip::class)]
final class FlipTest extends TestCase
{
    /**
     * @param array{int, int} $expected
     */
    #[DataProvider('flipDirectionProvider')]
    public function testTopLeftCornerIsMovedByTheDirection(FlipDirection $flipDirection, array $expected): void
    {
        $gdImage = imagecreatetruecolor(4, 2);
        imagesetpixel($gdImage, 0, 0, 0xFF0000);

        $gdImage = (new Flip($flipDirection))->apply($gdImage, new ImageInfo(['hasAlphaChannel' => false]));

        $this->assertSame(0xFF0000, imagecolorat($gdImage, ...$expected));
    }

    /**
     * @return iterable<string, array{FlipDirection, array{int, int}}>
     */
    public static function flipDirectionProvider(): iterable
    {
        yield 'horizontal' => [FlipDirection::Horizontal, [3, 0]];
        yield 'vertical' => [FlipDirection::Vertical, [0, 1]];
        yield 'both' => [FlipDirection::Both, [3, 1]];
    }

    public function testDirectionIsRequiredToRestoreFromArrays(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing flip direction');
        Flip::fromArray([]);
    }
}
