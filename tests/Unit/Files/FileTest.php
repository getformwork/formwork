<?php

namespace Formwork\Tests\Unit\Files;

use Formwork\Files\Exceptions\FileUriGenerationException;
use Formwork\Files\File;
use Formwork\Files\FileUriGenerator;
use Formwork\Parsers\Yaml;
use Formwork\Schemes\Scheme;
use Formwork\Tests\TestCase;
use Formwork\Utils\Exceptions\FileNotFoundException;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(File::class)]
final class FileTest extends TestCase
{
    protected function setUp(): void
    {
        $this->setUpTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
    }

    public function testPathNameExtensionAndStringConversionUseTheGivenPath(): void
    {
        $file = new File(TESTS_TMP_PATH . '/nested/report.final.txt');

        $this->assertSame(TESTS_TMP_PATH . '/nested/report.final.txt', $file->path());
        $this->assertSame('report.final.txt', $file->name());
        $this->assertSame('txt', $file->extension());
        $this->assertSame('report.final.txt', (string) $file);
    }

    public function testMissingFileMetadataDelegatesToFilesystemAndThrows(): void
    {
        $file = new File(TESTS_TMP_PATH . '/missing.txt');

        $this->expectException(FileNotFoundException::class);
        $file->size();
    }

    public function testMetadataIsReadLazilyAndContentHashIsStable(): void
    {
        $path = TESTS_TMP_PATH . '/sample.txt';
        FileSystem::write($path, 'hello');
        $file = new File($path);

        $this->assertSame('text/plain', $file->mimeType());
        $this->assertSame('text', $file->type());
        $this->assertSame('5 B', $file->size());
        $this->assertIsInt($file->lastModifiedTime());
        $this->assertSame(hash_file('sha256', $path), $file->contentHash());
        $this->assertSame($file->contentHash(), $file->contentHash());
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $file->hash());
    }

    #[DataProvider('typeProvider')]
    public function testTypeRecognizesSupportedExtensionsWithTheirMimeTypes(string $extension, string $mime, string $type): void
    {
        $path = TESTS_TMP_PATH . '/sample.' . $extension;
        FileSystem::write($path, 'placeholder');
        $file = new class ($path, $mime) extends File {
            public function __construct(string $path, string $mimeType)
            {
                parent::__construct($path);
                $this->mimeType = $mimeType;
            }
        };

        // File::type caches the MIME value in the model; seed it through the public accessor.
        $this->assertSame($type, $file->type());
    }

    public static function typeProvider(): iterable
    {
        yield 'pdf' => ['pdf', 'application/pdf', 'pdf'];
        yield 'document' => ['docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'document'];
        yield 'spreadsheet' => ['xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'spreadsheet'];
        yield 'presentation' => ['pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'presentation'];
        yield 'archive' => ['zip', 'application/zip', 'archive'];
    }

    public function testUnknownMimeAndExtensionProducesNullType(): void
    {
        $path = TESTS_TMP_PATH . '/sample.bin';
        FileSystem::write($path, "\0\1\2");
        $file = new File($path);

        $this->assertNull($file->type());
    }

    public function testUriRequiresGeneratorAndDelegatesRelativeAndAbsoluteGeneration(): void
    {
        $file = new File(TESTS_TMP_PATH . '/sample.txt');
        $this->expectException(FileUriGenerationException::class);
        $this->expectExceptionMessage('generator not set');
        $file->uri();
    }

    public function testUriMethodsUseTheConfiguredGenerator(): void
    {
        $file = new File(TESTS_TMP_PATH . '/sample.txt');
        $generator = $this->createMock(FileUriGenerator::class);
        $generator->expects($this->once())->method('generate')->with($file)->willReturn('/files/sample.txt');
        $generator->expects($this->once())->method('generateAbsolute')->with($file)->willReturn('https://example.test/files/sample.txt');
        $file->setUriGenerator($generator);

        $this->assertSame('/files/sample.txt', $file->uri());
        $this->assertSame('https://example.test/files/sample.txt', $file->absoluteUri());
    }

    public function testAnExplicitSchemeIsReturnedForTheFile(): void
    {
        $file = new File(TESTS_TMP_PATH . '/sample.txt');
        $scheme = $this->createStub(Scheme::class);

        $file->setScheme($scheme);

        $this->assertSame($scheme, $file->scheme());
    }

    public function testToArrayExportsPublicFileMetadataButNotInternalProperties(): void
    {
        $path = TESTS_TMP_PATH . '/sample.txt';
        FileSystem::write($path, 'hello');
        $file = new File($path);
        $file->set('caption', 'A sample');

        $data = $file->toArray();

        $this->assertSame('A sample', $data['caption']);
        $this->assertSame($path, $data['path']);
        $this->assertSame('sample.txt', $data['name']);
        $this->assertArrayNotHasKey('hash', $data);
        $this->assertArrayNotHasKey('contentHash', $data);
        $this->assertArrayNotHasKey('uriGenerator', $data);
    }

    public function testSaveRemovesStaleMetadataWhenThereIsNoUserData(): void
    {
        $path = TESTS_TMP_PATH . '/sample.txt';
        $meta = $path . '.meta.yaml';
        FileSystem::write($path, 'hello');
        FileSystem::write($meta, "title: stale\n");

        (new File($path))->save();

        $this->assertFileDoesNotExist($meta);
    }

    public function testSaveWritesUserMetadataToTheConfiguredSidecarFile(): void
    {
        $path = TESTS_TMP_PATH . '/sample.txt';
        $meta = $path . '.meta.yaml';
        FileSystem::write($path, 'hello');
        $file = new File($path);
        $file->set('caption', 'A saved caption');
        $file->set('details', ['author' => 'Sempronius']);

        $file->save();

        $this->assertFileExists($meta);
        $this->assertSame(
            [
                'caption' => 'A saved caption',
                'details' => ['author' => 'Sempronius'],
            ],
            Yaml::parseFile($meta),
        );
    }

    public function testStringRepresentationUsesTheFileName(): void
    {
        $path = TESTS_TMP_PATH . '/document.txt';
        FileSystem::write($path, 'content');
        $file = new File($path);

        $this->assertSame('document.txt', (string) $file);
        $this->assertSame('document.txt', $file->name());
        $this->assertSame('txt', $file->extension());
    }

    public function testTypeClassificationIsConsistentWithTheMimeTypeAndExtension(): void
    {
        $path = TESTS_TMP_PATH . '/document.pdf';
        FileSystem::write($path, '%PDF-1.4');
        $file = new File($path);

        $this->assertSame('pdf', $file->type());
        $this->assertSame($file->type(), $file->type());
    }
}
