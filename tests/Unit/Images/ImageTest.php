<?php

namespace Formwork\Tests\Unit\Images;

use Formwork\Cms\App;
use Formwork\Files\Exceptions\FileUriGenerationException;
use Formwork\Images\Image;
use Formwork\Images\ImageFactory;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Image::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class ImageTest extends TestCase
{
    public function testImagesWithoutUriGeneratorCanBeProcessed(): void
    {
        $path = TESTS_TMP_PATH . '/image.png';
        imagepng(imagecreatetruecolor(200, 100), $path);

        // Images made by the image factory, unlike the ones made by the file factory, have no URI generator
        $image = App::instance()->getService(ImageFactory::class)->make($path, ['processPath' => TESTS_TMP_PATH . '/processed']);

        $processed = $image->resize(50, 25)->process();

        $this->assertNotSame($path, $processed->path());
        $this->assertSame(50, $processed->info()->width());
        $this->assertSame(25, $processed->info()->height());

        // The missing URI generator is reported only when an URI is requested
        $this->expectException(FileUriGenerationException::class);
        $processed->uri();
    }
}
