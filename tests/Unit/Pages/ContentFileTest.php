<?php

namespace Formwork\Tests\Unit\Pages;

use Formwork\Pages\ContentFile;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(ContentFile::class)]
#[UsesTempDirectory]
final class ContentFileTest extends TestCase
{
    public function testParsesFrontmatterAndNormalizesContentLineEndings(): void
    {
        $path = TESTS_PATH . '/Unit/Pages/Fixtures/site/about/page.md';
        $contentFile = new ContentFile($path);

        $this->assertSame(['title' => 'About'], $contentFile->frontmatter());
        $this->assertSame('This page introduces the project and explains where to find the main sections of the site.', $contentFile->content());
        $this->assertFalse($contentFile->isEmpty());
    }

    public function testEmptyContentFileIsReportedAsEmpty(): void
    {
        $contentFile = new ContentFile(TESTS_PATH . '/Unit/Pages/Fixtures/site/empty/page.md');

        $this->assertSame([], $contentFile->frontmatter());
        $this->assertSame('', $contentFile->content());
        $this->assertTrue($contentFile->isEmpty());
    }

    public function testInvalidPageFormatIsRejected(): void
    {
        $path = TESTS_TMP_PATH . '/invalid-page.md';
        FileSystem::write($path, 'not a page');

        $this->expectException(UnexpectedValueException::class);
        new ContentFile($path);
    }
}
