<?php

namespace Formwork\Tests\Unit\Images;

use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Images\Image;
use Formwork\Images\ImageFactory;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\Path;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(ImageFactory::class)]
#[UsesClass(Image::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class ImageFactoryTest extends TestCase
{
    public function testImagesUseTheConfiguredOptions(): void
    {
        $processed = $this->imageFactory()->make($this->imagePath())->resize(20, 10)->process();

        $this->assertTrue(Path::isRelativeTo($processed->path(), TESTS_TMP_PATH . '/configured'));
    }

    public function testGivenOptionsOverrideTheConfiguredOnes(): void
    {
        $processed = $this->imageFactory()->make($this->imagePath(), ['processPath' => TESTS_TMP_PATH . '/given'])->resize(20, 10)->process();

        $this->assertTrue(Path::isRelativeTo($processed->path(), TESTS_TMP_PATH . '/given'));
    }

    private function imageFactory(): ImageFactory
    {
        /** @var array<string, mixed> */
        $options = App::instance()->config()->getArray('system.images');

        return new ImageFactory(new Config(['system' => ['images' => ['processPath' => TESTS_TMP_PATH . '/configured'] + $options]], resolved: true));
    }

    private function imagePath(): string
    {
        $path = TESTS_TMP_PATH . '/image.png';
        imagepng(imagecreatetruecolor(40, 20), $path);

        return $path;
    }
}
