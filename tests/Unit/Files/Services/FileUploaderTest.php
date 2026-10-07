<?php

namespace Formwork\Tests\Unit\Files\Services;

use Formwork\Config\Config;
use Formwork\Exceptions\TranslatedException;
use Formwork\Files\File;
use Formwork\Files\FileFactory;
use Formwork\Files\FileUriGenerator;
use Formwork\Files\Services\FileUploader;
use Formwork\Http\Files\UploadedFile;
use Formwork\Services\Container;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FileUploader::class)]
final class FileUploaderTest extends TestCase
{
    protected function setUp(): void
    {
        $this->setUpTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
    }

    public function testAllowedMimeTypesAreDerivedFromConfiguredExtensions(): void
    {
        $uploader = new FileUploader($this->config(['txt', 'jpg']), new FileFactory(new Container(), $this->config(['txt', 'jpg'])));

        $this->assertSame(['text/plain', 'image/jpeg'], $uploader->allowedMimeTypes());
    }

    public function testUploadRejectsAFileThatWasNotUploaded(): void
    {
        $uploaded = $this->uploadedFile(UPLOAD_ERR_NO_FILE, 'missing.txt');
        $uploader = new FileUploader($this->config(['txt']), new FileFactory(new Container(), $this->config(['txt'])));

        $this->expectException(TranslatedException::class);
        $this->expectExceptionMessage('No file uploaded');
        $this->expectExceptionCode(0);
        $uploader->upload($uploaded, TESTS_TMP_PATH);
    }

    public function testUploadRejectsAMIMETypeOutsideTheAllowedList(): void
    {
        $temp = TESTS_TMP_PATH . '/payload.bin';
        FileSystem::write($temp, 'binary payload');
        $uploaded = $this->uploadedFile(UPLOAD_ERR_OK, 'payload.bin', $temp);
        $uploader = new FileUploader($this->config(['jpg']), new FileFactory(new Container(), $this->config(['jpg'])));

        $this->expectException(TranslatedException::class);
        $this->expectExceptionMessage('Invalid mime type text/plain');
        $uploader->upload($uploaded, TESTS_TMP_PATH);
    }

    public function testUploadRejectsDestinationsOutsideConfiguredBaseDirectories(): void
    {
        $temp = TESTS_TMP_PATH . '/payload.txt';
        FileSystem::write($temp, 'payload');
        $uploaded = $this->uploadedFile(UPLOAD_ERR_OK, 'payload.txt', $temp);
        $uploader = new FileUploader($this->config(['txt'], ['/allowed']), new FileFactory(new Container(), $this->config(['txt'], ['/allowed'])));

        $this->expectException(TranslatedException::class);
        $this->expectExceptionMessage('Invalid destination path');
        $uploader->upload($uploaded, '/outside');
    }

    public function testUploadNormalizesNameChoosesClientExtensionAndReturnsTheFactoryFile(): void
    {
        $temp = TESTS_TMP_PATH . '/payload.txt';
        FileSystem::write($temp, 'payload');
        $uploaded = $this->createMock(UploadedFile::class);
        $uploaded->method('isUploaded')->willReturn(true);
        $uploaded->method('tempPath')->willReturn($temp);
        $uploaded->method('clientName')->willReturn('My Unsafe Name.TXT');
        $uploaded->expects($this->once())->method('move')->with(TESTS_TMP_PATH, 'renamed-file.txt', true)
            ->willReturnCallback(fn(string $destination, string $name) => (bool) FileSystem::write($destination . '/' . $name, 'payload'));
        $file = new File(TESTS_TMP_PATH . '/renamed-file.txt');
        $container = $this->fileFactoryContainer($file);
        $factory = new FileFactory($container, $this->config(['txt'], [TESTS_TMP_PATH]));
        $uploader = new FileUploader($this->config(['txt'], [TESTS_TMP_PATH]), $factory);

        $this->assertSame($file, $uploader->upload($uploaded, TESTS_TMP_PATH, 'Renamed File', overwrite: true));
    }

    public function testUploadUsesTheMimetypeExtensionWhenTheClientExtensionIsNotAssociated(): void
    {
        $temp = TESTS_TMP_PATH . '/payload.txt';
        FileSystem::write($temp, 'payload');
        $uploaded = $this->createMock(UploadedFile::class);
        $uploaded->method('isUploaded')->willReturn(true);
        $uploaded->method('tempPath')->willReturn($temp);
        $uploaded->method('clientName')->willReturn('photo.exe');
        $uploaded->expects($this->once())->method('move')->with(TESTS_TMP_PATH, 'photo.txt', false)
            ->willReturnCallback(fn(string $destination, string $name) => (bool) FileSystem::write($destination . '/' . $name, 'payload'));
        $file = new File(TESTS_TMP_PATH . '/photo.txt');
        $factory = new FileFactory($this->fileFactoryContainer($file), $this->config(['txt'], [TESTS_TMP_PATH]));

        $this->assertSame($file, (new FileUploader($this->config(['txt'], [TESTS_TMP_PATH]), $factory))->upload($uploaded, TESTS_TMP_PATH));
    }

    private function config(array $extensions, array $destinations = [TESTS_TMP_PATH]): Config
    {
        return new Config(['system' => [
            'files' => [
                'allowedExtensions' => $extensions,
                'metadataExtension' => '.meta.yaml',
                'uploads'           => ['baseDestinations' => $destinations],
            ],
            'uploads' => ['processImages' => false],
        ]], resolved: true);
    }

    private function uploadedFile(int $error, string $name, ?string $tempPath = null): UploadedFile
    {
        return new UploadedFile('upload', [
            'name'      => $name,
            'full_path' => $name,
            'type'      => 'text/plain',
            'tmp_name'  => $tempPath ?? TESTS_TMP_PATH . '/missing',
            'error'     => (string) $error,
            'size'      => '0',
        ]);
    }

    private function fileFactoryContainer(File $file): Container
    {
        $container = $this->createMock(Container::class);
        $container->method('build')->willReturn($file);
        $container->method('get')->with(FileUriGenerator::class)->willReturn($this->createStub(FileUriGenerator::class));
        return $container;
    }
}
