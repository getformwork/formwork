<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\Contrast;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Contrast::class)]
final class ContrastTest extends TestCase
{
    #[DataProvider('amountProvider')]
    public function testPositiveAmountsIncreaseTheContrast(int $amount, int $expected): void
    {
        $gdImage = imagecreatetruecolor(1, 1);
        imagesetpixel($gdImage, 0, 0, 0x804020);

        $gdImage = (new Contrast($amount))->apply($gdImage, new ImageInfo(['hasAlphaChannel' => false]));

        $this->assertSame($expected, imagecolorat($gdImage, 0, 0));
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function amountProvider(): iterable
    {
        // Channels are moved away from or towards the middle gray
        yield 'more contrast' => [50, 0x800000];
        yield 'less contrast' => [-50, 0x7F6F67];
    }

    #[DataProvider('invalidAmountProvider')]
    public function testAmountMustBeInRange(int $amount): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("\$amount value must be in range -100-+100, {$amount} given");
        new Contrast($amount);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidAmountProvider(): iterable
    {
        yield 'too low' => [-101];
        yield 'too high' => [101];
    }
}
