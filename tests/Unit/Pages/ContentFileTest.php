<?php

namespace Formwork\Tests\Unit\Pages;

use Formwork\Pages\ContentFile;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(ContentFile::class)]
final class ContentFileTest extends TestCase
{
    public function testParsesFrontmatterAndNormalizesContentLineEndings(): void
    {
        $path = TESTS_PATH . '/Unit/Pages/Fixtures/site/about/page.md';
        $file = new ContentFile($path);

        $this->assertSame(['title' => 'About'], $file->frontmatter());
        $this->assertSame('This page introduces the project and explains where to find the main sections of the site.', $file->content());
        $this->assertFalse($file->isEmpty());
    }

    public function testEmptyFrontmatterIsReportedAsEmpty(): void
    {
        $file = new ContentFile(TESTS_PATH . '/Unit/Pages/Fixtures/site/empty/page.md');

        $this->assertSame([], $file->frontmatter());
        $this->assertTrue($file->isEmpty());
    }

    public function testInvalidPageFormatIsRejected(): void
    {
        $path = TESTS_TMP_PATH . '/invalid-page.md';
        $this->setUpTempDirectory();
        FileSystem::write($path, 'not a page');

        try {
            $this->expectException(UnexpectedValueException::class);
            new ContentFile($path);
        } finally {
            $this->tearDownTempDirectory();
        }
    }
}
