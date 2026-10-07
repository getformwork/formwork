<?php

namespace Formwork\Tests\Unit\Files;

use Formwork\Config\Config;
use Formwork\Files\File;
use Formwork\Files\FileFactory;
use Formwork\Files\FileUriGenerator;
use Formwork\Services\Container;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

#[CoversClass(FileFactory::class)]
final class FileFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        $this->setUpTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
    }

    public function testMakeBuildsAFileLoadsMetadataAndInjectsTheUriGenerator(): void
    {
        $path = TESTS_TMP_PATH . '/sample.txt';
        FileSystem::write($path, 'hello');
        FileSystem::write($path . '.meta.yaml', "caption: Example\n");
        $file = new File($path);
        $uriGenerator = $this->createMock(FileUriGenerator::class);
        $uriGenerator->expects($this->once())->method('generate')->with($file)->willReturn('/files/sample.txt');
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('build')->with(File::class, ['path' => $path])->willReturn($file);
        $container->expects($this->once())->method('get')->with(FileUriGenerator::class)->willReturn($uriGenerator);
        $config = new Config(['system' => ['files' => ['metadataExtension' => '.meta.yaml']]], resolved: true);

        $result = (new FileFactory($container, $config))->make($path);

        $this->assertSame($file, $result);
        $this->assertSame('Example', $result->get('caption'));
        $this->assertSame('/files/sample.txt', $result->uri());
    }

    public function testMakeUsesTheDefaultFileClassWhenMimeTypeHasNoAssociation(): void
    {
        $path = TESTS_TMP_PATH . '/sample.txt';
        FileSystem::write($path, 'hello');
        $file = new File($path);
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('build')->with(File::class, ['path' => $path])->willReturn($file);
        $container->method('get')->willReturn($this->createStub(FileUriGenerator::class));
        $config = new Config(['system' => ['files' => ['metadataExtension' => '.meta.yaml']]], resolved: true);

        $this->assertSame($file, (new FileFactory($container, $config))->make($path));
    }

    public function testMakeBuildsAnAssociatedClass(): void
    {
        $path = TESTS_TMP_PATH . '/sample.txt';
        FileSystem::write($path, 'hello');
        $file = new File($path);
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('build')->with(File::class, ['path' => $path])->willReturn($file);
        $container->method('get')->willReturn($this->createStub(FileUriGenerator::class));
        $config = new Config([
            'system' => ['files' => ['metadataExtension' => '.meta.yaml']],
        ], resolved: true);

        // The association is keyed by the MIME type discovered from the controlled file.
        $factory = new FileFactory($container, $config, ['text/plain' => File::class]);

        $this->assertSame($file, $factory->make($path));
    }

    public function testMakeRejectsAContainerResultThatIsNotAFile(): void
    {
        $path = TESTS_TMP_PATH . '/sample.txt';
        FileSystem::write($path, 'hello');
        $container = $this->createStub(Container::class);
        $container->method('build')->willReturn(new \stdClass());
        $config = new Config(['system' => ['files' => ['metadataExtension' => '.meta.yaml']]], resolved: true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('only instances of Formwork\Files\File are allowed');
        (new FileFactory($container, $config))->make($path);
    }

    public function testMakeUsesAnAssociatedFactoryMethod(): void
    {
        $path = TESTS_TMP_PATH . '/sample.txt';
        FileSystem::write($path, 'hello');
        $result = new File($path);
        $built = new FactoryFile($path);
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('build')->with(FactoryFile::class, ['path' => $path])->willReturn($built);
        $container->expects($this->once())->method('call')->willReturn($result);
        $container->method('get')->with(FileUriGenerator::class)->willReturn($this->createStub(FileUriGenerator::class));
        $config = new Config(['system' => ['files' => ['metadataExtension' => '.meta.yaml']]], resolved: true);

        $this->assertSame($result, (new FileFactory($container, $config, [
            'text/plain' => [FactoryFile::class, 'fromPath'],
        ]))->make($path));
    }
}

final class FactoryFile extends File
{
    public function fromPath(string $path): File
    {
        return new File($path);
    }
}
