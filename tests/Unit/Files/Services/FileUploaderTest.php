<?php

namespace Formwork\Tests\Unit\Files\Services;

use Formwork\Cms\App;
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

    public function testUploadRejectsTraversalOutOfTheBaseDestinations(): void
    {
        $temp = TESTS_TMP_PATH . '/payload.txt';
        FileSystem::write($temp, 'payload');
        FileSystem::createDirectory(TESTS_TMP_PATH . '/allowed');
        $uploader = new FileUploader(
            $this->config(['txt'], [TESTS_TMP_PATH . '/allowed']),
            new FileFactory(new Container(), $this->config(['txt'])),
        );

        $this->expectException(TranslatedException::class);
        $this->expectExceptionMessage('Invalid destination path');
        $uploader->upload($this->movableUpload($temp, 'payload.txt'), TESTS_TMP_PATH . '/allowed/../outside');
    }

    public function testUploadRejectsDirectoriesSharingOnlyAPrefixWithTheBaseDestination(): void
    {
        $temp = TESTS_TMP_PATH . '/payload.txt';
        FileSystem::write($temp, 'payload');
        FileSystem::createDirectory(TESTS_TMP_PATH . '/allowed-evil');
        $uploader = new FileUploader(
            $this->config(['txt'], [TESTS_TMP_PATH . '/allowed']),
            new FileFactory(new Container(), $this->config(['txt'])),
        );

        $this->expectException(TranslatedException::class);
        $this->expectExceptionMessage('Invalid destination path');
        $uploader->upload($this->movableUpload($temp, 'payload.txt'), TESTS_TMP_PATH . '/allowed-evil');
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

    public function testUploadAcceptsSubdirectoriesOfTheBaseDestinations(): void
    {
        $temp = TESTS_TMP_PATH . '/payload.txt';
        FileSystem::write($temp, 'payload');
        FileSystem::createDirectory(TESTS_TMP_PATH . '/allowed/nested', recursive: true);
        $file = new File(TESTS_TMP_PATH . '/allowed/nested/payload.txt');
        $uploader = new FileUploader(
            $this->config(['txt'], [TESTS_TMP_PATH . '/allowed']),
            new FileFactory($this->fileFactoryContainer($file), $this->config(['txt'])),
        );

        $this->assertSame($file, $uploader->upload($this->movableUpload($temp, 'payload.txt'), TESTS_TMP_PATH . '/allowed/nested'));
        $this->assertFileExists(TESTS_TMP_PATH . '/allowed/nested/payload.txt');
    }

    public function testUploadValidatesTheRealContentTypeInsteadOfTheClientOnes(): void
    {
        $temp = TESTS_TMP_PATH . '/image.jpg';
        FileSystem::write($temp, 'this is plain text, not an image');
        $uploaded = $this->movableUpload($temp, 'image.jpg');
        $uploader = new FileUploader($this->config(['jpg']), new FileFactory(new Container(), $this->config(['jpg'])));

        $this->expectException(TranslatedException::class);
        $this->expectExceptionMessage('Invalid mime type text/plain');
        $uploader->upload($uploaded, TESTS_TMP_PATH);
    }

    public function testExplicitAllowedMimeTypesReplaceTheConfiguredOnes(): void
    {
        $temp = TESTS_TMP_PATH . '/payload.txt';
        FileSystem::write($temp, 'payload');
        $file = new File(TESTS_TMP_PATH . '/payload.txt');
        $uploader = new FileUploader($this->config(['jpg']), new FileFactory($this->fileFactoryContainer($file), $this->config(['jpg'])));

        $this->assertSame($file, $uploader->upload($this->movableUpload($temp, 'payload.txt'), TESTS_TMP_PATH, allowedMimeTypes: ['text/plain']));

        $restricted = new FileUploader($this->config(['txt']), new FileFactory(new Container(), $this->config(['txt'])));

        $this->expectException(TranslatedException::class);
        $this->expectExceptionMessage('Invalid mime type text/plain');
        $restricted->upload($this->movableUpload($temp, 'payload.txt'), TESTS_TMP_PATH, allowedMimeTypes: ['image/png']);
    }

    public function testUploadedNamesAlwaysProduceAVisibleFileNameWithAnExtension(): void
    {
        $temp = TESTS_TMP_PATH . '/payload.txt';
        FileSystem::write($temp, 'payload');
        $uploaded = $this->createMock(UploadedFile::class);
        $uploaded->method('isUploaded')->willReturn(true);
        $uploaded->method('tempPath')->willReturn($temp);
        $uploaded->method('clientName')->willReturn('!!!.txt');
        $uploaded->expects($this->once())->method('move')
            ->with(TESTS_TMP_PATH, $this->callback(static fn(string $name): bool => !str_starts_with($name, '.') && str_ends_with($name, '.txt')), false)
            ->willReturn(true);
        $factory = new FileFactory($this->fileFactoryContainer(new File(TESTS_TMP_PATH . '/payload.txt')), $this->config(['txt']));

        (new FileUploader($this->config(['txt']), $factory))->upload($uploaded, TESTS_TMP_PATH);
    }

    public function testUploadedSvgImagesAreSanitized(): void
    {
        $temp = TESTS_TMP_PATH . '/source.svg';
        FileSystem::write($temp, '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><script>alert(1)</script><path d="M0 0h16v16H0z" onload="alert(2)"/></svg>');
        FileSystem::createDirectory(TESTS_TMP_PATH . '/uploads');
        $config = $this->config(['svg'], [TESTS_TMP_PATH]);
        $uploader = new FileUploader($config, App::instance()->getService(FileFactory::class));

        $file = $uploader->upload($this->movableUpload($temp, 'icon.svg'), TESTS_TMP_PATH . '/uploads');

        $this->assertSame(TESTS_TMP_PATH . '/uploads/icon.svg', $file->path());
        $content = FileSystem::read($file->path());
        $this->assertStringContainsString('<path', $content);
        $this->assertStringNotContainsString('<script', $content);
        $this->assertStringNotContainsString('onload', $content);
    }

    private function movableUpload(string $temp, string $clientName): UploadedFile
    {
        $uploaded = $this->createStub(UploadedFile::class);
        $uploaded->method('isUploaded')->willReturn(true);
        $uploaded->method('tempPath')->willReturn($temp);
        $uploaded->method('clientName')->willReturn($clientName);
        $uploaded->method('move')->willReturnCallback(
            static fn(string $destination, string $name): bool => (bool) FileSystem::write(FileSystem::joinPaths($destination, $name), FileSystem::read($temp)),
        );

        return $uploaded;
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
