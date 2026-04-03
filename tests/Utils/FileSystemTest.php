<?php

namespace Formwork\Tests\Utils;

use Formwork\Tests\TestCase;
use Formwork\Tests\Utils\Fixtures\FileSystemFixture;
use Formwork\Utils\Exceptions\FileNotFoundException;
use Formwork\Utils\Exceptions\FileSystemException;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

#[CoversClass(FileSystem::class)]
final class FileSystemTest extends TestCase
{
    protected function setUp(): void
    {
        FileSystemFixture::enableAll();
        FileSystem::copyDirectory(__DIR__ . '/fixtures/files/tmp', TESTS_TMP_PATH, overwrite: true);
        FileSystem::createDirectory(TESTS_TMP_PATH . '/emptydir');
    }

    protected function tearDown(): void
    {
        FileSystemFixture::enableAll();
        $this->tearDownTempDirectory();
    }

    public function testNormalize(): void
    {
        $this->assertSame('path/to/directory', FileSystem::normalizePath('path\to/directory'));
        $this->assertSame('path/to/directory', FileSystem::normalizePath('path//to\directory'));
    }

    public function testJoinPaths(): void
    {
        $this->assertSame('path/to/directory/file.txt', FileSystem::joinPaths('path/to', 'directory', 'file.txt'));
        $this->assertSame('path/to/directory/file.txt', FileSystem::joinPaths('path\to\\', '/directory/', '\file.txt'));
    }

    public function testResolve(): void
    {
        $this->assertSame('/var/www/html', FileSystem::resolvePath('/var/www/html/../html'));
        $this->assertSame('C:/Projects/Formwork', FileSystem::resolvePath('C:\Projects\Formwork\..\Formwork'));
    }

    public function testBasename(): void
    {
        $this->assertSame('file', FileSystem::name('/path/to/file.txt'));
        $this->assertSame('directory', FileSystem::name('/path/to/directory/'));
    }

    public function testExtension(): void
    {
        $this->assertSame('txt', FileSystem::extension('/path/to/file.txt'));
        $this->assertSame('', FileSystem::extension('/path/to/directory/'));
    }

    public function testCwd(): void
    {
        $this->assertSame(getcwd(), FileSystem::cwd());
    }

    #[RunInSeparateProcess]
    public function testCwdThrowsOnUnresolved(): void
    {
        FileSystemFixture::disable('cwd');
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('Cannot get current working directory');
        FileSystem::cwd();
    }

    public function testIsVisible(): void
    {
        $this->assertTrue(FileSystem::isVisible('/path/to/visibleFile.txt'));
        $this->assertFalse(FileSystem::isVisible('/path/to/.hiddenFile.txt'));
    }

    public function testMimeType(): void
    {
        $this->assertSame('text/plain', FileSystem::mimeType(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testExists(): void
    {
        $this->assertTrue(FileSystem::exists(TESTS_TMP_PATH . '/sample.txt'));
        $this->assertFalse(FileSystem::exists(TESTS_TMP_PATH . '/nonexistent.txt'));
    }

    public function testAssertExists(): void
    {
        FileSystem::assertExists(TESTS_TMP_PATH . '/sample.txt');
        $this->assertTrue(true);

        $this->expectException(FileNotFoundException::class);
        FileSystem::assertExists(TESTS_TMP_PATH . '/nonexistent.txt');
    }

    public function testAssertNotExists(): void
    {
        FileSystem::assertExists(TESTS_TMP_PATH . '/nonexistent.txt', false);
        $this->assertTrue(true);

        $this->expectException(FileSystemException::class);
        FileSystem::assertExists(TESTS_TMP_PATH . '/sample.txt', false);
    }

    public function testIsReadable(): void
    {
        $this->assertTrue(FileSystem::isReadable(TESTS_TMP_PATH . '/sample.txt'));

        $mode = fileperms(TESTS_TMP_PATH . '/sample.txt');
        chmod(TESTS_TMP_PATH . '/sample.txt', $mode & ~0o444);

        $this->assertFalse(FileSystem::isReadable(TESTS_TMP_PATH . '/sample.txt'));

        chmod(TESTS_TMP_PATH . '/sample.txt', $mode);
    }

    public function testIsWritable(): void
    {
        $this->assertTrue(FileSystem::isWritable(TESTS_TMP_PATH . '/sample.txt'));

        $mode = fileperms(TESTS_TMP_PATH . '/sample.txt');
        chmod(TESTS_TMP_PATH . '/sample.txt', $mode & ~0o222);

        $this->assertFalse(FileSystem::isWritable(TESTS_TMP_PATH . '/sample.txt'));

        chmod(TESTS_TMP_PATH . '/sample.txt', $mode);
    }

    public function testIsFile(): void
    {
        $this->assertTrue(FileSystem::isFile(TESTS_TMP_PATH . '/sample.txt'));
        $this->assertFalse(FileSystem::isFile(TESTS_TMP_PATH . '/dir'));
    }

    public function testIsDirectory(): void
    {
        $this->assertTrue(FileSystem::isDirectory(TESTS_TMP_PATH . '/dir'));
        $this->assertTrue(FileSystem::isDirectory(TESTS_TMP_PATH . '/emptydir'));
        $this->assertFalse(FileSystem::isDirectory(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testIsEmptyDirectory(): void
    {
        $this->assertTrue(FileSystem::isEmptyDirectory(TESTS_TMP_PATH . '/emptydir'));
        $this->assertFalse(FileSystem::isEmptyDirectory(TESTS_TMP_PATH . '/dir'));
        $this->assertFalse(FileSystem::isEmptyDirectory(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testIsLink(): void
    {
        $this->assertTrue(FileSystem::isLink(TESTS_TMP_PATH . '/symlink'));
        $this->assertFalse(FileSystem::isLink(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testAccessTime(): void
    {
        $this->assertIsInt(FileSystem::accessTime(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testAccessTimeThrowsOnFileNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::accessTime(TESTS_TMP_PATH . '/nonexistent.txt');
    }

    #[RunInSeparateProcess]
    public function testAccessTimeThrowsOnSystemError(): void
    {
        FileSystemFixture::disable('fileatime');
        $this->expectException(FileSystemException::class);
        FileSystem::accessTime(TESTS_TMP_PATH . '/dir/sample.txt');
    }

    public function testCreationTime(): void
    {
        $this->assertIsInt(FileSystem::creationTime(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testCreationTimeThrowsOnFileNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::creationTime(TESTS_TMP_PATH . '/nonexistent.txt');
    }

    #[RunInSeparateProcess]
    public function testCreationTimeThrowsOnSystemError(): void
    {
        FileSystemFixture::disable('filectime');
        $this->expectException(FileSystemException::class);
        FileSystem::creationTime(TESTS_TMP_PATH . '/dir/sample.txt');
    }

    public function testLastModifiedTime(): void
    {
        $this->assertIsInt(FileSystem::lastModifiedTime(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testLastModifiedTimeThrowsOnNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::lastModifiedTime(TESTS_TMP_PATH . '/nonexistent.txt');
    }

    #[RunInSeparateProcess]
    public function testLastModifiedTimeThrowsOnSystemError(): void
    {
        FileSystemFixture::disable('filemtime');
        $this->expectException(FileSystemException::class);
        FileSystem::lastModifiedTime(TESTS_TMP_PATH . '/dir/sample.txt');
    }

    public function testDirectoryModifiedSince(): void
    {
        $this->assertTrue(FileSystem::directoryModifiedSince(TESTS_TMP_PATH . '/dir', 0));
        $this->assertFalse(FileSystem::directoryModifiedSince(TESTS_TMP_PATH . '/dir', time() + 3600));
    }

    public function testDirectoryModifiedSinceRecursively(): void
    {
        touch(TESTS_TMP_PATH . '/dir', 0);
        touch(TESTS_TMP_PATH . '/dir/subdir', 0);
        $this->assertTrue(FileSystem::directoryModifiedSince(TESTS_TMP_PATH . '/dir', 10));
    }

    public function testDirectoryModifiedThrowsOnNotDir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileSystem::directoryModifiedSince(TESTS_TMP_PATH . '/sample.txt', 0);
    }

    public function testTouch(): void
    {
        $this->assertTrue(FileSystem::touch(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testTouchThrowsOnFileNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::touch(TESTS_TMP_PATH . '/nonexistent.txt');
    }

    #[RunInSeparateProcess]
    public function testTouchThrowsOnSystemError(): void
    {
        FileSystemFixture::disable('touch');
        $this->expectException(FileSystemException::class);
        FileSystem::touch(TESTS_TMP_PATH . '/sample.txt');
    }

    public function testMode(): void
    {
        $this->assertIsInt(FileSystem::mode(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testModeThrowsOnFileNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::mode(TESTS_TMP_PATH . '/nonexistent.txt');
    }

    #[RunInSeparateProcess]
    public function testModeThrowsOnSystemError(): void
    {
        FileSystemFixture::disable('fileperms');
        $this->expectException(FileSystemException::class);
        FileSystem::mode(TESTS_TMP_PATH . '/sample.txt');
    }

    public function testSize(): void
    {
        $this->assertIsInt(FileSystem::size(TESTS_TMP_PATH . '/sample.txt'));
        $this->assertIsInt(FileSystem::size(TESTS_TMP_PATH . '/dir'));
    }

    public function testSizeThrowsOnUnsupportedType(): void
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('unsupported file type');
        FileSystem::size('/dev/null');
    }

    public function testFileSizeReturnsSize(): void
    {
        $this->assertIsInt(FileSystem::fileSize(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testFileSizeThrowsOnNotFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileSystem::fileSize(TESTS_TMP_PATH . '/dir');
    }

    public function testFileSizeThrowsOnFileNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::fileSize(TESTS_TMP_PATH . '/nonexistent.txt');
    }

    #[RunInSeparateProcess]
    public function testFileSizeThrowsOnSystemError(): void
    {
        FileSystemFixture::disable('filesize');
        $this->expectException(FileSystemException::class);
        FileSystem::fileSize(TESTS_TMP_PATH . '/sample.txt');
    }

    public function testDirectorySizeReturnsSize(): void
    {
        $this->assertIsInt(FileSystem::directorySize(TESTS_TMP_PATH . '/dir'));
    }

    public function testDirectorySizeThrowsOnNotDir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileSystem::directorySize(TESTS_TMP_PATH . '/sample.txt');
    }

    public function testDirectorySizeThrowsOnNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::directorySize(TESTS_TMP_PATH . '/nonexistentdir');
    }

    #[RunInSeparateProcess]
    public function testDirectorySizeThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('filesize');
        $this->expectException(FileSystemException::class);
        FileSystem::directorySize(TESTS_TMP_PATH . '/dir');
    }

    public function testDelete(): void
    {
        $this->assertTrue(FileSystem::delete(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testDeleteThrowsOnUnsupportedType(): void
    {
        // Create a FIFO special file for testing
        posix_mkfifo(TESTS_TMP_PATH . '/myfifo', 0o600);

        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('unsupported file type');

        try {
            FileSystem::delete(TESTS_TMP_PATH . '/myfifo');
        } finally {
            // Clean up the FIFO file
            unlink(TESTS_TMP_PATH . '/myfifo');
        }
    }

    public function testDeleteFile(): void
    {
        $this->assertTrue(FileSystem::deleteFile(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testDeleteFileThrowsOnNotFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileSystem::deleteFile(TESTS_TMP_PATH . '/dir');
    }

    public function testDeleteFileThrowsOnFileNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::deleteFile(TESTS_TMP_PATH . '/nonexistent.txt');
    }

    #[RunInSeparateProcess]
    public function testDeleteFileThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('unlink');
        $this->expectException(FileSystemException::class);
        FileSystem::deleteFile(TESTS_TMP_PATH . '/sample.txt');
    }

    public function testDeleteDirectory(): void
    {
        $this->assertTrue(FileSystem::deleteDirectory(TESTS_TMP_PATH . '/emptydir'));
    }

    public function testDeleteDirectoryThrowsOnNotEmpty(): void
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('must be empty to be deleted');
        FileSystem::deleteDirectory(TESTS_TMP_PATH . '/dir');
    }

    public function testDeleteDirectoryRecursively(): void
    {
        $this->assertTrue(FileSystem::deleteDirectory(TESTS_TMP_PATH . '/dir', recursive: true));
    }

    public function testDeleteDirectoryThrowsOnNotDir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileSystem::deleteDirectory(TESTS_TMP_PATH . '/sample.txt');
    }

    public function testDeleteDirectoryThrowsOnNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::deleteDirectory(TESTS_TMP_PATH . '/nonexistentdir');
    }

    #[RunInSeparateProcess]
    public function testDeleteDirectoryThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('rmdir');
        $this->expectException(FileSystemException::class);
        FileSystem::deleteDirectory(TESTS_TMP_PATH . '/emptydir');
    }

    public function testDeleteLink(): void
    {
        $this->assertTrue(FileSystem::deleteLink(TESTS_TMP_PATH . '/symlink'));
    }

    public function testDeleteLinkThrowsOnNotLink(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileSystem::deleteLink(TESTS_TMP_PATH . '/sample.txt');
    }

    public function testDeleteLinkThrowsOnLinkNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::deleteLink(TESTS_TMP_PATH . '/nonexistentlink');
    }

    #[RunInSeparateProcess]
    public function testDeleteLinkThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('unlink');
        $this->expectException(FileSystemException::class);
        FileSystem::deleteLink(TESTS_TMP_PATH . '/symlink');
    }

    public function testCopy(): void
    {
        $this->assertTrue(FileSystem::copy(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_copy.txt'));

        $this->assertTrue(FileSystem::copy(TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir_copy'));

        $this->assertTrue(FileSystem::copy(TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/symlink_copy'));
    }

    public function testCopyThrowsOnUnsupportedType(): void
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('unsupported file type');
        FileSystem::copy('/dev/null', TESTS_TMP_PATH . '/null_copy');
    }

    public function testCopyFile(): void
    {
        $this->assertTrue(FileSystem::copyFile(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_copy.txt'));
    }

    public function testCopyFileThrowsOnNotFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileSystem::copyFile(TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir_copy');
    }

    public function testCopyFileThrowsOnFileNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::copyFile(TESTS_TMP_PATH . '/nonexistent.txt', TESTS_TMP_PATH . '/nonexistent_copy.txt');
    }

    public function testCopyFileThrowsOnDestExists(): void
    {
        $this->expectException(FileSystemException::class);
        FileSystem::copyFile(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample.txt');
    }

    #[RunInSeparateProcess]
    public function testCopyFileThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('copy');
        $this->expectException(FileSystemException::class);
        FileSystem::copyFile(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_copy.txt');
    }

    public function testCopyDirectory(): void
    {
        $this->assertTrue(FileSystem::copyDirectory(TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir_copy'));
    }

    public function testCopyDirectoryThrowsOnNotDir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileSystem::copyDirectory(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_copy.txt');
    }

    public function testCopyDirectoryThrowsOnNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::copyDirectory(TESTS_TMP_PATH . '/nonexistentdir', TESTS_TMP_PATH . '/nonexistentdir_copy');
    }

    public function testCopyDirectoryThrowsOnDestExists(): void
    {
        $this->expectException(FileSystemException::class);
        FileSystem::copyDirectory(TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir');
    }

    #[RunInSeparateProcess]
    public function testCopyDirectoryThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('copy');
        $this->expectException(FileSystemException::class);
        FileSystem::copyDirectory(TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir_copy');
    }

    public function testCopyLink(): void
    {
        $this->assertTrue(FileSystem::copyLink(TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/symlink_copy'));
    }

    public function testCopyLinkWithOverwrite(): void
    {
        $this->assertTrue(FileSystem::copyLink(TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/dir/b.txt', overwrite: true));
    }

    public function testCopyLinkThrowsOnNotLink(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileSystem::copyLink(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_link');
    }

    public function testCopyLinkThrowsOnLinkNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::copyLink(TESTS_TMP_PATH . '/nonexistentlink', TESTS_TMP_PATH . '/nonexistentlink_copy');
    }

    public function testCopyLinkThrowsOnDestExists(): void
    {
        $this->expectException(FileSystemException::class);
        FileSystem::copyLink(TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/symlink');
    }

    #[RunInSeparateProcess]
    public function testCopyLinkThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('symlink');
        $this->expectException(FileSystemException::class);
        FileSystem::copyLink(TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/symlink_copy');
    }

    public function testMove(): void
    {
        $this->assertTrue(FileSystem::move(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_moved.txt'));
        $this->assertFalse(FileSystem::exists(TESTS_TMP_PATH . '/sample.txt'));

        $this->assertTrue(FileSystem::move(TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir_moved'));
        $this->assertFalse(FileSystem::exists(TESTS_TMP_PATH . '/dir'));

        $this->assertTrue(FileSystem::move(TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/symlink_moved'));
        $this->assertFalse(FileSystem::exists(TESTS_TMP_PATH . '/symlink'));
    }

    public function testMoveThrowsOnUnsupportedType(): void
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('unsupported file type');
        FileSystem::move('/dev/null', TESTS_TMP_PATH . '/null_moved');
    }

    public function testMoveFile(): void
    {
        $this->assertTrue(FileSystem::moveFile(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_moved.txt'));
        $this->assertFalse(FileSystem::exists(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testMoveFileThrowsOnNotFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileSystem::moveFile(TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir_moved');
    }

    public function testMoveFileThrowsOnFileNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::moveFile(TESTS_TMP_PATH . '/nonexistent.txt', TESTS_TMP_PATH . '/nonexistent_moved.txt');
    }

    public function testMoveFileThrowsOnDestExists(): void
    {
        $this->expectException(FileSystemException::class);
        FileSystem::moveFile(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample.txt');
    }

    #[RunInSeparateProcess]
    public function testMoveFileThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('rename');
        $this->expectException(FileSystemException::class);
        FileSystem::moveFile(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_moved.txt');
    }

    public function testMoveDirectory(): void
    {
        $this->assertTrue(FileSystem::moveDirectory(TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir_moved'));
        $this->assertFalse(FileSystem::exists(TESTS_TMP_PATH . '/dir'));
    }

    public function testMoveDirectoryThrowsOnNotDir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileSystem::moveDirectory(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_moved.txt');
    }

    public function testMoveDirectoryThrowsOnNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::moveDirectory(TESTS_TMP_PATH . '/nonexistentdir', TESTS_TMP_PATH . '/nonexistentdir_moved');
    }

    public function testMoveDirectoryThrowsOnDestExists(): void
    {
        $this->expectException(FileSystemException::class);
        FileSystem::moveDirectory(TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir');
    }

    #[RunInSeparateProcess]
    public function testMoveDirectoryThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('copy');
        $this->expectException(FileSystemException::class);
        FileSystem::moveDirectory(TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir_moved');
    }

    public function testMoveLink(): void
    {
        $this->assertTrue(FileSystem::moveLink(TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/symlink_moved'));
        $this->assertFalse(FileSystem::exists(TESTS_TMP_PATH . '/symlink'));
    }

    public function testMoveLinkThrowsOnNotLink(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileSystem::moveLink(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_moved');
    }

    public function testMoveLinkThrowsOnLinkNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::moveLink(TESTS_TMP_PATH . '/nonexistentlink', TESTS_TMP_PATH . '/nonexistentlink_moved');
    }

    public function testMoveLinkThrowsOnDestExists(): void
    {
        $this->expectException(FileSystemException::class);
        FileSystem::moveLink(TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/symlink');
    }

    #[RunInSeparateProcess]
    public function testMoveLinkThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('symlink');
        $this->expectException(FileSystemException::class);
        FileSystem::moveLink(TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/symlink_moved');
    }

    public function testRead(): void
    {
        $this->assertSame("This is a sample text file for testing purposes.\n", FileSystem::read(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testReadThrowsOnNotFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileSystem::read(TESTS_TMP_PATH . '/dir');
    }

    public function testReadThrowsOnFileNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::read(TESTS_TMP_PATH . '/nonexistent.txt');
    }

    public function testReadThrowsOnFileUnreadable(): void
    {
        $mode = fileperms(TESTS_TMP_PATH . '/sample.txt');
        chmod(TESTS_TMP_PATH . '/sample.txt', $mode & ~0o444);

        $this->expectException(FileSystemException::class);
        FileSystem::read(TESTS_TMP_PATH . '/sample.txt');

        chmod(TESTS_TMP_PATH . '/sample.txt', $mode);
    }

    #[RunInSeparateProcess]
    public function testReadThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('file_get_contents');
        $this->expectException(FileSystemException::class);
        FileSystem::read(TESTS_TMP_PATH . '/sample.txt');
    }

    public function testListContents(): void
    {
        $contents = iterator_to_array(FileSystem::listContents(TESTS_TMP_PATH . '/dir'));
        $this->assertCount(3, $contents);
    }

    public function testListContentsIncludingHidden(): void
    {
        $contents = iterator_to_array(FileSystem::listContents(TESTS_TMP_PATH . '/dir', FileSystem::LIST_ALL));
        $this->assertContains('.hidden', $contents);
    }

    public function testListContentsFilesOnly(): void
    {
        $contents = iterator_to_array(FileSystem::listContents(TESTS_TMP_PATH . '/dir', FileSystem::LIST_FILES));
        $this->assertNotContains('subdir', $contents);
    }

    public function testListContentsDirectoriesOnly(): void
    {
        $contents = iterator_to_array(FileSystem::listContents(TESTS_TMP_PATH . '/dir', FileSystem::LIST_DIRECTORIES));
        $this->assertNotContains('.hidden', $contents);
        $this->assertNotContains('b.txt', $contents);
        $this->assertNotContains('sample.txt', $contents);
    }

    public function testListContentsExcludingEmptyDirectories(): void
    {
        $contents = iterator_to_array(FileSystem::listContents(TESTS_TMP_PATH, FileSystem::LIST_VISIBLE | FileSystem::LIST_EXCLUDE_EMPTY_DIRECTORIES));
        $this->assertNotContains('emptydir', $contents);
    }

    public function testListContentsThrowsOnNotDir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        iterator_to_array(FileSystem::listContents(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testListContentsThrowsOnNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        iterator_to_array(FileSystem::listContents(TESTS_TMP_PATH . '/nonexistentdir'));
    }

    #[RunInSeparateProcess]
    public function testListContentsThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('opendir');
        $this->expectException(FileSystemException::class);
        iterator_to_array(FileSystem::listContents(TESTS_TMP_PATH . '/dir'));
    }

    public function testListContentsRecursively(): void
    {
        $contents = iterator_to_array(FileSystem::listRecursive(TESTS_TMP_PATH));
        $this->assertContains('dir/subdir/a.txt', $contents);
    }

    public function testListRecursiveThrowsOnNotDir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        iterator_to_array(FileSystem::listRecursive(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testListFiles(): void
    {
        $files = iterator_to_array(FileSystem::listFiles(TESTS_TMP_PATH . '/dir'));
        $this->assertContains('b.txt', $files);
        $this->assertContains('sample.txt', $files);
        $this->assertNotContains('subdir', $files);
        $this->assertNotContains('.hidden', $files);
    }

    public function testListFilesIncludingHidden(): void
    {
        $files = iterator_to_array(FileSystem::listFiles(TESTS_TMP_PATH . '/dir', includeHidden: true));
        $this->assertContains('.hidden', $files);
    }

    public function testListDirectories(): void
    {
        $dirs = iterator_to_array(FileSystem::listDirectories(TESTS_TMP_PATH . '/dir'));
        $this->assertContains('subdir', $dirs);
        $this->assertNotContains('b.txt', $dirs);
        $this->assertNotContains('sample.txt', $dirs);
        $this->assertNotContains('.hidden', $dirs);
    }

    public function testListDirectoriesIncludingHidden(): void
    {
        FileSystem::createDirectory(TESTS_TMP_PATH . '/dir/.hiddendir');
        $dirs = iterator_to_array(FileSystem::listDirectories(TESTS_TMP_PATH . '/dir', includeHidden: true));
        $this->assertContains('.hiddendir', $dirs);
    }

    public function testListDirectoriesExcludingEmpty(): void
    {
        $dirs = iterator_to_array(FileSystem::listDirectories(TESTS_TMP_PATH, includeEmpty: false));
        $this->assertNotContains('emptydir', $dirs);
    }

    public function testReadLink(): void
    {
        $this->assertSame('sample.txt', FileSystem::readLink(TESTS_TMP_PATH . '/symlink'));
    }

    public function testReadLinkThrowsOnNotLink(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileSystem::readLink(TESTS_TMP_PATH . '/sample.txt');
    }

    public function testReadLinkThrowsOnLinkNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        FileSystem::readLink(TESTS_TMP_PATH . '/nonexistentlink');
    }

    #[RunInSeparateProcess]
    public function testReadLinkThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('readlink');
        $this->expectException(FileSystemException::class);
        FileSystem::readLink(TESTS_TMP_PATH . '/symlink');
    }

    public function testCreateFile(): void
    {
        $this->assertTrue(FileSystem::createFile(TESTS_TMP_PATH . '/newfile.txt'));
        $this->assertTrue(FileSystem::exists(TESTS_TMP_PATH . '/newfile.txt'));
    }

    #[RunInSeparateProcess]
    public function testCreateFileThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('fopen');
        $this->expectException(FileSystemException::class);
        FileSystem::createFile(TESTS_TMP_PATH . '/newfile.txt');
    }

    public function testCreateTemporaryFile(): void
    {
        $tempFile = FileSystem::createTemporaryFile(TESTS_TMP_PATH, '_tmp');
        $this->assertTrue(FileSystem::exists($tempFile));
        $this->assertStringStartsWith('_tmp', basename($tempFile));
    }

    #[RunInSeparateProcess]
    public function testCreateTempFileThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('fopen');
        $this->expectException(FileSystemException::class);
        FileSystem::createTemporaryFile(TESTS_TMP_PATH, '_tmp');
    }

    public function testWrite(): void
    {
        FileSystem::write(TESTS_TMP_PATH . '/newfile.txt', 'Hello, World!');
        $this->assertSame('Hello, World!', FileSystem::read(TESTS_TMP_PATH . '/newfile.txt'));
    }

    public function testWriteToExistingFile(): void
    {
        chmod(TESTS_TMP_PATH . '/sample.txt', 0o644);
        FileSystem::write(TESTS_TMP_PATH . '/sample.txt', 'Hello, World!');
        $this->assertSame('Hello, World!', FileSystem::read(TESTS_TMP_PATH . '/sample.txt'));
        $this->assertSame(0o644, FileSystem::mode(TESTS_TMP_PATH . '/sample.txt') & 0o777);
    }

    public function testWriteThrowsOnNotFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileSystem::write(TESTS_TMP_PATH . '/dir', 'Hello, World!');
    }

    public function testWriteThrowsOnFileUnwritable(): void
    {
        $mode = fileperms(TESTS_TMP_PATH . '/sample.txt');
        chmod(TESTS_TMP_PATH . '/sample.txt', $mode & ~0o222);

        $this->expectException(FileSystemException::class);
        FileSystem::write(TESTS_TMP_PATH . '/sample.txt', 'Hello, World!');

        chmod(TESTS_TMP_PATH . '/sample.txt', $mode);
    }

    #[RunInSeparateProcess]
    public function testWriteThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('file_put_contents');
        $this->expectException(FileSystemException::class);
        FileSystem::write(TESTS_TMP_PATH . '/newfile.txt', 'Hello, World!');
    }

    public function testCreateDirectory(): void
    {
        $this->assertTrue(FileSystem::createDirectory(TESTS_TMP_PATH . '/newdir'));
        $this->assertTrue(FileSystem::exists(TESTS_TMP_PATH . '/newdir'));
    }

    #[RunInSeparateProcess]
    public function testCreateDirectoryThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('mkdir');
        $this->expectException(FileSystemException::class);
        FileSystem::createDirectory(TESTS_TMP_PATH . '/newdir');
    }

    public function testCreateLink(): void
    {
        $this->assertTrue(FileSystem::createLink(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/newlink'));
        $this->assertTrue(FileSystem::isLink(TESTS_TMP_PATH . '/newlink'));
    }

    #[RunInSeparateProcess]
    public function testCreateLinkThrowsExceptionOnSystemError(): void
    {
        FileSystemFixture::disable('symlink');
        $this->expectException(FileSystemException::class);
        FileSystem::createLink(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/newlink');
    }

    public function testFormatSize(): void
    {
        $this->assertSame('0 B', FileSystem::formatSize(-1));
        $this->assertSame('0 B', FileSystem::formatSize(0));
        $this->assertSame('1 B', FileSystem::formatSize(1));
        $this->assertSame('1 KB', FileSystem::formatSize(1024));
        $this->assertSame('1 MB', FileSystem::formatSize(1024 ** 2));
        $this->assertSame('1 GB', FileSystem::formatSize(1024 ** 3));
        $this->assertSame('1 TB', FileSystem::formatSize(1024 ** 4));
        $this->assertSame('1024 TB', FileSystem::formatSize(1024 ** 5));
    }

    public function testShorthandToBytes(): void
    {
        $this->assertSame(1048576, FileSystem::shorthandToBytes('1M'));
        $this->assertSame(1073741824, FileSystem::shorthandToBytes('1G'));
        $this->assertSame(512, FileSystem::shorthandToBytes('512'));
        $this->assertSame(2048, FileSystem::shorthandToBytes('2K'));
    }

    public function testShorthandToBytesThrowsOnInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileSystem::shorthandToBytes('1T');
    }

    public function testRandomName(): void
    {
        $name1 = FileSystem::randomName();
        $name2 = FileSystem::randomName();
        $this->assertNotSame($name1, $name2);
        $this->assertSame(16, strlen($name1));
        $this->assertSame(16, strlen($name2));
    }

    public function testRandomNameWithPrefix(): void
    {
        $name = FileSystem::randomName('test_');
        $this->assertStringStartsWith('test_', $name);
    }
}
