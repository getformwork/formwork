<?php

namespace Formwork\Tests\Utils;

use Formwork\Tests\Environment;
use Formwork\Tests\TestCase;
use Formwork\Utils\MimeType;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

#[CoversClass(MimeType::class)]
final class MimeTypeTest extends TestCase
{
    protected function tearDown(): void
    {
        Environment::enableExtension('fileinfo');
    }

    public function testFromExtension(): void
    {
        $this->assertSame('image/jpeg', MimeType::fromExtension('jpg'));
        $this->assertSame('text/plain', MimeType::fromExtension('txt'));
        $this->assertSame('application/pdf', MimeType::fromExtension('pdf'));
        $this->assertSame('application/octet-stream', MimeType::fromExtension('unknown_extension'));
    }

    public function testFromFile(): void
    {
        $this->assertSame('text/html', MimeType::fromFile(__DIR__ . '/fixtures/files/mimetype/sample.html'));
        $this->assertSame('text/yaml', MimeType::fromFile(__DIR__ . '/fixtures/files/mimetype/sample.yaml'));
    }

    public function testFromFileWithSvg(): void
    {
        $this->assertSame('image/svg+xml', MimeType::fromFile(__DIR__ . '/fixtures/files/mimetype/valid.svg'));
        $this->assertSame('application/octet-stream', MimeType::fromFile(__DIR__ . '/fixtures/files/mimetype/invalid.svg'));
    }

    public function testFromFileThrowsOnDisabledFileinfo(): void
    {
        Environment::disableExtension('fileinfo');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires the extension "fileinfo" to be enabled');
        MimeType::fromFile(__DIR__ . '/fixtures/MimeType/sample.html');
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

    public function testType(): void
    {
        $extensionTypes = MimeType::extensionTypes();
        $this->assertIsArray($extensionTypes);
    }
}
