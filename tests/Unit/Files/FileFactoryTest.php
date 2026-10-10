<?php

namespace Formwork\Tests\Unit\Files;

use Closure;
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

    public function testMakeBuildsTheDefaultFileClassWhenTheMimeTypeHasNoAssociation(): void
    {
        $path = TESTS_TMP_PATH . '/sample.txt';
        FileSystem::write($path, 'hello');
        $file = new File($path);
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('build')->with(File::class, ['path' => $path])->willReturn($file);
        $container->method('get')->willReturn($this->createStub(FileUriGenerator::class));

        $factory = new FileFactory($container, $this->config(), ['image/png' => AssociatedFile::class]);

        $this->assertSame($file, $factory->make($path));
    }

    public function testMakeBuildsTheClassAssociatedWithTheDetectedMimeType(): void
    {
        $path = TESTS_TMP_PATH . '/sample.txt';
        FileSystem::write($path, 'hello');
        $file = new AssociatedFile($path);
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('build')->with(AssociatedFile::class, ['path' => $path])->willReturn($file);
        $container->method('get')->willReturn($this->createStub(FileUriGenerator::class));

        $factory = new FileFactory($container, $this->config(), ['text/plain' => AssociatedFile::class]);

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

    public function testMakeCallsTheAssociatedFactoryMethodOnTheBuiltObject(): void
    {
        $path = TESTS_TMP_PATH . '/sample.txt';
        FileSystem::write($path, 'hello');
        $builder = new AssociatedFile($path);
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('build')->with(AssociatedFile::class, ['path' => $path])->willReturn($builder);
        $container->expects($this->once())->method('call')
            ->willReturnCallback(static fn(Closure $factory, array $parameters): mixed => $factory(...$parameters));
        $container->method('get')->willReturn($this->createStub(FileUriGenerator::class));

        $factory = new FileFactory($container, $this->config(), ['text/plain' => [AssociatedFile::class, 'fromPath']]);
        $result = $factory->make($path);

        $this->assertNotSame($builder, $result);
        $this->assertInstanceOf(AssociatedFile::class, $result);
        $this->assertSame($path, $result->path());
        $this->assertSame('created', $result->origin);
    }

    private function config(): Config
    {
        return new Config(['system' => ['files' => ['metadataExtension' => '.meta.yaml']]], resolved: true);
    }
}

final class AssociatedFile extends File
{
    public string $origin = 'constructed';

    public function fromPath(string $path): static
    {
        $file = new static($path);
        $file->origin = 'created';

        return $file;
    }
}
