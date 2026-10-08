<?php

namespace Formwork\Tests\Unit\Files;

use Formwork\Cms\App;
use Formwork\Fields\FieldCollection;
use Formwork\Fields\FieldFactory;
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
use ZipArchive;

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

    #[DataProvider('contentTypeProvider')]
    public function testTypeIsDerivedFromTheDetectedMimeType(string $filename, string $content, ?string $expectedType): void
    {
        $path = TESTS_TMP_PATH . '/' . $filename;
        FileSystem::write($path, $content);

        $this->assertSame($expectedType, (new File($path))->type());
    }

    /**
     * @return iterable<string, array{string, string, ?string}>
     */
    public static function contentTypeProvider(): iterable
    {
        $png = (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGP4z8DwHwAFAAH/iZk9HQAAAABJRU5ErkJggg==', true);

        yield 'text' => ['note.txt', 'hello', 'text'];
        yield 'markdown' => ['note.md', '# Title', 'text'];
        yield 'image' => ['picture.png', $png, 'image'];
        yield 'pdf' => ['report.pdf', '%PDF-1.4', 'pdf'];
        yield 'zip archive' => ['bundle.zip', self::zipContent(), 'archive'];
        yield 'gzip archive' => ['bundle.gz', (string) gzencode('content'), 'archive'];
        yield 'content wins over the extension' => ['picture.txt', $png, 'image'];
        yield 'unknown binary file' => ['blob.bin', "\0\1\2", null];
    }

    #[DataProvider('mimeTypeProvider')]
    public function testTypeIsMappedFromTheMimeType(string $mimeType, ?string $expectedType): void
    {
        $path = TESTS_TMP_PATH . '/sample.dat';
        FileSystem::write($path, 'placeholder');
        $file = new class ($path, $mimeType) extends File {
            public function __construct(string $path, string $mimeType)
            {
                parent::__construct($path);
                $this->mimeType = $mimeType;
            }
        };

        $this->assertSame($expectedType, $file->type());
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function mimeTypeProvider(): iterable
    {
        yield 'word document' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'document'];
        yield 'legacy word document' => ['application/msword', 'document'];
        yield 'open document text' => ['application/vnd.oasis.opendocument.text', 'document'];
        yield 'presentation' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'presentation'];
        yield 'spreadsheet' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'spreadsheet'];
        yield '7z archive' => ['application/x-7z-compressed', 'archive'];
        yield 'rar archive' => ['application/x-rar-compressed', 'archive'];
        yield 'plain text' => ['text/plain', 'text'];
        yield 'unmapped image' => ['image/x-unmapped', null];
        yield 'unmapped application' => ['application/x-unmapped', null];
    }

    private static function zipContent(): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'zip');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('entry.txt', 'content');
        $zip->close();
        $content = (string) file_get_contents($path);
        unlink($path);

        return $content;
    }

    public function testTypeIsComputedOnlyOnce(): void
    {
        $path = TESTS_TMP_PATH . '/note.txt';
        FileSystem::write($path, 'hello');
        $file = new File($path);

        $this->assertSame('text', $file->type());

        FileSystem::delete($path);

        $this->assertSame('text', $file->type());
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

    public function testSaveStoresOnlyValuesDifferentFromTheSchemeDefaults(): void
    {
        $path = TESTS_TMP_PATH . '/sample.txt';
        $meta = $path . '.meta.yaml';
        FileSystem::write($path, 'hello');

        $fields = new FieldCollection([
            'caption' => $this->app()->getService(FieldFactory::class)->make('caption', ['type' => 'text', 'default' => 'untitled']),
        ]);
        $scheme = $this->createStub(Scheme::class);
        $scheme->method('fields')->willReturn($fields);

        $file = new File($path);
        $file->setScheme($scheme);
        $file->set('caption', 'untitled');
        $file->save();

        $this->assertFileDoesNotExist($meta);

        $file->set('caption', 'A custom caption');
        $file->save();

        $this->assertSame(['caption' => 'A custom caption'], Yaml::parseFile($meta));
    }

    private function app(): App
    {
        return App::instance();
    }
}
