<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\Brightness;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Brightness::class)]
final class BrightnessTest extends TestCase
{
    #[DataProvider('amountProvider')]
    public function testAmountChangesTheBrightnessOfEachChannel(int $amount, int $expected): void
    {
        $gdImage = imagecreatetruecolor(1, 1);
        imagesetpixel($gdImage, 0, 0, 0x804020);

        $gdImage = (new Brightness($amount))->apply($gdImage, new ImageInfo(['hasAlphaChannel' => false]));

        $this->assertSame($expected, imagecolorat($gdImage, 0, 0));
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function amountProvider(): iterable
    {
        yield 'brighter' => [50, 0xB27252];
        yield 'darker, down to black' => [-50, 0x4E0E00];
    }

    #[DataProvider('invalidAmountProvider')]
    public function testAmountMustBeInRange(int $amount): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("\$amount value must be in range -255-+255, {$amount} given");
        new Brightness($amount);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidAmountProvider(): iterable
    {
        yield 'too low' => [-256];
        yield 'too high' => [256];
    }
}
