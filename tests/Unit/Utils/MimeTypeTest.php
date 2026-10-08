<?php

namespace Formwork\Tests\Unit\Utils;

use Formwork\Tests\Environment;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use Formwork\Utils\MimeType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

#[CoversClass(MimeType::class)]
final class MimeTypeTest extends TestCase
{
    protected function setUp(): void
    {
        $this->setUpTempDirectory();
    }

    protected function tearDown(): void
    {
        Environment::enableExtension('fileinfo');
        $this->tearDownTempDirectory();
    }

    public function testFromExtension(): void
    {
        $this->assertSame('image/jpeg', MimeType::fromExtension('jpg'));
        $this->assertSame('text/plain', MimeType::fromExtension('txt'));
        $this->assertSame('application/pdf', MimeType::fromExtension('pdf'));
        $this->assertSame('application/octet-stream', MimeType::fromExtension('unknown_extension'));
    }

    public function testFromExtensionAcceptsAnOptionalLeadingDot(): void
    {
        $this->assertSame('image/jpeg', MimeType::fromExtension('.jpg'));
        $this->assertSame('application/octet-stream', MimeType::fromExtension(''));
    }

    public function testExtensionsAreCaseInsensitive(): void
    {
        $this->assertSame('image/jpeg', MimeType::fromExtension('JPG'));
        $this->assertSame('application/pdf', MimeType::fromExtension('Pdf'));
    }

    #[DataProvider('fileProvider')]
    public function testFromFileDetectsTheTypeFromTheContentAndTheExtension(string $filename, string $content, string $expected): void
    {
        FileSystem::write(TESTS_TMP_PATH . '/' . $filename, $content);

        $this->assertSame($expected, MimeType::fromFile(TESTS_TMP_PATH . '/' . $filename));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function fileProvider(): iterable
    {
        yield 'plain text' => ['note.txt', 'hello', 'text/plain'];
        yield 'CSV is kept as plain text' => ['data.csv', "a,b\n1,2\n", 'text/plain'];
        yield 'unknown extension with text content' => ['note.exe', 'hello', 'text/plain'];
        yield 'stylesheet' => ['style.css', "body {\n  color: red;\n}\n.a { margin: 0 }\n", 'text/css'];
        yield 'uppercase stylesheet extension' => ['STYLE.CSS', "body {\n  color: red;\n}\n", 'text/css'];
        yield 'script' => ['script.js', "function a() {\n  return 1;\n}\nconsole.log(a());\n", 'text/javascript'];
        yield 'Markdown' => ['doc.md', "# Title\n\ntext\n", 'text/markdown'];
        yield 'YAML' => ['conf.yaml', "key: value\n", 'text/yaml'];
        yield 'JSON' => ['data.json', '{"a":1}', 'application/json'];
        yield 'HTML' => ['page.html', '<!DOCTYPE html><html><body>hi</body></html>', 'text/html'];
        yield 'PHP source is not hidden behind a safe extension' => ['code.php', '<?php echo 1;', 'text/x-php'];
        yield 'text with an SVG extension' => ['fake.svg', 'not an image at all', 'text/plain'];
        yield 'SVG with an XML declaration' => ['decl.svg', "<?xml version=\"1.0\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\"><rect width=\"1\" height=\"1\"/></svg>", 'image/svg+xml'];
        yield 'SVG without an XML declaration' => ['plain.svg', '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>', 'image/svg+xml'];
        yield 'SVG without a namespace' => ['bare.svg', '<svg viewBox="0 0 1 1"><rect/></svg>', 'image/svg+xml'];
        yield 'truncated SVG' => ['broken.svg', '<svg xmlns="http://www.w3.org/2000/svg"', 'application/octet-stream'];
        yield 'empty file' => ['empty.dat', '', 'application/x-empty'];
    }

    #[DataProvider('unreadableFileProvider')]
    public function testFromFileRejectsMissingFilesDirectoriesAndEmptyPaths(string $path): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist or is not readable');
        MimeType::fromFile($path);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unreadableFileProvider(): iterable
    {
        yield 'empty path' => [''];
        yield 'missing file' => [TESTS_TMP_PATH . '/missing.txt'];
        yield 'directory' => [__DIR__];
    }

    public function testFromFileThrowsOnDisabledFileinfo(): void
    {
        Environment::disableExtension('fileinfo');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires the extension "fileinfo" to be enabled');
        MimeType::fromFile(__DIR__ . '/Fixtures/files/mimetype/sample.html');
    }

    public function testExtensions(): void
    {
        $this->assertSame(['jpg', 'jpeg', 'jpe'], MimeType::getAssociatedExtensions('image/jpeg'));
        $this->assertSame([], MimeType::getAssociatedExtensions('unknown/mime-type'));
    }

    public function testToExtension(): void
    {
        $this->assertSame('jpg', MimeType::toExtension('image/jpeg'));
    }

    public function testExtensionTypesListsEveryExtensionWithItsMimeType(): void
    {
        $extensionTypes = MimeType::extensionTypes();

        $this->assertSame('.jpg (image/jpeg)', $extensionTypes['.jpg']);
        $this->assertSame('.pdf (application/pdf)', $extensionTypes['.pdf']);
        $this->assertArrayNotHasKey('jpg', $extensionTypes);
    }

    public function testEveryExtensionRoundTripsThroughItsMimeType(): void
    {
        foreach (['jpg', 'png', 'pdf', 'txt', 'html', 'svg', 'json'] as $extension) {
            $mimeType = MimeType::fromExtension($extension);

            $this->assertContains($extension, MimeType::getAssociatedExtensions($mimeType), $extension);
        }
    }
}
