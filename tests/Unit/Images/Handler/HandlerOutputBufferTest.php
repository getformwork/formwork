<?php

namespace Formwork\Tests\Unit\Images\Handler;

use ErrorException;
use Formwork\Images\Handler\AbstractHandler;
use Formwork\Images\Handler\AvifHandler;
use Formwork\Images\Handler\GifHandler;
use Formwork\Images\Handler\JpegHandler;
use Formwork\Images\Handler\PngHandler;
use Formwork\Images\Handler\WebpHandler;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(PngHandler::class)]
#[CoversClass(JpegHandler::class)]
#[CoversClass(GifHandler::class)]
#[CoversClass(WebpHandler::class)]
#[CoversClass(AvifHandler::class)]
final class HandlerOutputBufferTest extends TestCase
{
    /**
     * @param class-string<AbstractHandler> $handler
     */
    #[DataProvider('handlerProvider')]
    public function testImagesAreOutputWithoutLeavingOutputBuffersOpen(string $handler, string $function): void
    {
        if (!function_exists($function)) {
            $this->markTestSkipped("The GD extension is built without {$function}().");
        }

        $level = ob_get_level();

        $this->assertNotSame('', $handler::fromGdImage(imagecreatetruecolor(4, 4))->getData());
        $this->assertSame($level, ob_get_level());
    }

    /**
     * @return iterable<string, array{class-string<AbstractHandler>, string}>
     */
    public static function handlerProvider(): iterable
    {
        yield 'PNG' => [PngHandler::class, 'imagepng'];
        yield 'JPEG' => [JpegHandler::class, 'imagejpeg'];
        yield 'GIF' => [GifHandler::class, 'imagegif'];
        yield 'WebP' => [WebpHandler::class, 'imagewebp'];
        yield 'AVIF' => [AvifHandler::class, 'imageavif'];
    }

    public function testOutputBuffersAreClosedWhenImagesCannotBeOutput(): void
    {
        $level = ob_get_level();

        // Like the error handler of the application, which converts warnings to exceptions
        set_error_handler(static fn(int $severity, string $message): never => throw new ErrorException($message, 0, $severity));

        try {
            // An invalid compression level makes libpng emit a warning
            $this->captureException(ErrorException::class, static fn() => PngHandler::fromGdImage(imagecreatetruecolor(4, 4), ['pngCompression' => 10]));
        } finally {
            restore_error_handler();
        }

        $this->assertSame($level, ob_get_level());
    }
}
