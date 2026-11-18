<?php

namespace Formwork\Tests\Unit\Utils;

use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Utils\Fixtures\NativeFunctionsFixture;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\Exceptions\FileNotFoundException;
use Formwork\Utils\Exceptions\FileSystemException;
use Formwork\Utils\FileSystem;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

#[CoversClass(FileSystem::class)]
#[UsesTempDirectory]
final class FileSystemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        NativeFunctionsFixture::enableAll();
        FileSystem::copyDirectory(__DIR__ . '/Fixtures/files/tmp', TESTS_TMP_PATH, overwrite: true);
        FileSystem::createDirectory(TESTS_TMP_PATH . '/emptydir');
    }

    protected function tearDown(): void
    {
        NativeFunctionsFixture::enableAll();
        parent::tearDown();
    }

    public function testNormalizePathUsesForwardSlashesOnly(): void
    {
        $this->assertSame('path/to/directory', FileSystem::normalizePath('path\to/directory'));
        $this->assertSame('path/to/directory', FileSystem::normalizePath('path//to\directory'));
    }

    public function testJoinPathsCollapsesTheSeparators(): void
    {
        $this->assertSame('path/to/directory/file.txt', FileSystem::joinPaths('path/to', 'directory', 'file.txt'));
        $this->assertSame('path/to/directory/file.txt', FileSystem::joinPaths('path\to\\', '/directory/', '\file.txt'));
    }

    public function testResolvePathRemovesParentReferences(): void
    {
        $this->assertSame('/var/www/html', FileSystem::resolvePath('/var/www/html/../html'));
        $this->assertSame('C:/Projects/Formwork', FileSystem::resolvePath('C:\Projects\Formwork\..\Formwork'));
    }

    public function testNameIsTheBasenameWithoutExtension(): void
    {
        $this->assertSame('file', FileSystem::name('/path/to/file.txt'));
        $this->assertSame('directory', FileSystem::name('/path/to/directory/'));
    }

    public function testExtensionIsEmptyForDirectories(): void
    {
        $this->assertSame('txt', FileSystem::extension('/path/to/file.txt'));
        $this->assertSame('', FileSystem::extension('/path/to/directory/'));
    }

    public function testCwdIsTheCurrentWorkingDirectory(): void
    {
        $this->assertSame(getcwd(), FileSystem::cwd());
    }

    public function testCwdReportsUnresolvedPaths(): void
    {
        NativeFunctionsFixture::disable('cwd');
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('Cannot get current working directory');
        FileSystem::cwd();
    }

    public function testIsVisibleRecognizesHiddenFiles(): void
    {
        $this->assertTrue(FileSystem::isVisible('/path/to/visibleFile.txt'));
        $this->assertFalse(FileSystem::isVisible('/path/to/.hiddenFile.txt'));
    }

    public function testMimeTypeIsDetectedFromTheFile(): void
    {
        $this->assertSame('text/plain', FileSystem::mimeType(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testExistsChecksThePath(): void
    {
        $this->assertTrue(FileSystem::exists(TESTS_TMP_PATH . '/sample.txt'));
        $this->assertFalse(FileSystem::exists(TESTS_TMP_PATH . '/nonexistent.txt'));
    }

    public function testAssertExistsAcceptsExistingPaths(): void
    {
        $this->expectNotToPerformAssertions();

        FileSystem::assertExists(TESTS_TMP_PATH . '/sample.txt');
        FileSystem::assertExists(TESTS_TMP_PATH . '/dir');
    }

    public function testAssertNotExistsAcceptsMissingPaths(): void
    {
        $this->expectNotToPerformAssertions();

        FileSystem::assertExists(TESTS_TMP_PATH . '/nonexistent.txt', false);
    }

    public function testIsReadableFollowsThePermissions(): void
    {
        $this->skipIfFilePermissionsAreNotEnforced();

        $this->assertTrue(FileSystem::isReadable(TESTS_TMP_PATH . '/sample.txt'));

        $mode = (int) fileperms(TESTS_TMP_PATH . '/sample.txt');
        chmod(TESTS_TMP_PATH . '/sample.txt', $mode & ~0o444);

        $this->assertFalse(FileSystem::isReadable(TESTS_TMP_PATH . '/sample.txt'));

        chmod(TESTS_TMP_PATH . '/sample.txt', $mode);
    }

    public function testIsWritableFollowsThePermissions(): void
    {
        $this->skipIfFilePermissionsAreNotEnforced();

        $this->assertTrue(FileSystem::isWritable(TESTS_TMP_PATH . '/sample.txt'));

        $mode = (int) fileperms(TESTS_TMP_PATH . '/sample.txt');
        chmod(TESTS_TMP_PATH . '/sample.txt', $mode & ~0o222);

        $this->assertFalse(FileSystem::isWritable(TESTS_TMP_PATH . '/sample.txt'));

        chmod(TESTS_TMP_PATH . '/sample.txt', $mode);
    }

    public function testIsFileRecognizesFiles(): void
    {
        $this->assertTrue(FileSystem::isFile(TESTS_TMP_PATH . '/sample.txt'));
        $this->assertFalse(FileSystem::isFile(TESTS_TMP_PATH . '/dir'));
    }

    public function testIsDirectoryRecognizesDirectories(): void
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

    public function testIsLinkRecognizesSymbolicLinks(): void
    {
        $this->assertTrue(FileSystem::isLink(TESTS_TMP_PATH . '/symlink'));
        $this->assertFalse(FileSystem::isLink(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testAccessTimeIsReadFromTheFile(): void
    {
        touch(TESTS_TMP_PATH . '/sample.txt', 1_700_000_000, 1_700_000_500);

        $this->assertSame(1_700_000_500, FileSystem::accessTime(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testCreationTimeIsTheTimeOfTheWrite(): void
    {
        $before = time();
        FileSystem::write(TESTS_TMP_PATH . '/created.txt', 'content');

        $this->assertGreaterThanOrEqual($before, FileSystem::creationTime(TESTS_TMP_PATH . '/created.txt'));
        $this->assertLessThanOrEqual(time(), FileSystem::creationTime(TESTS_TMP_PATH . '/created.txt'));
    }

    public function testLastModifiedTimeIsReadFromTheFile(): void
    {
        touch(TESTS_TMP_PATH . '/sample.txt', 1_700_000_000);

        $this->assertSame(1_700_000_000, FileSystem::lastModifiedTime(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testDirectoryModifiedSinceComparesWithTheGivenTime(): void
    {
        $this->assertTrue(FileSystem::directoryModifiedSince(TESTS_TMP_PATH . '/dir', 0));
        $this->assertFalse(FileSystem::directoryModifiedSince(TESTS_TMP_PATH . '/dir', time() + 3600));
    }

    public function testDirectoryModifiedSinceLooksIntoSubdirectories(): void
    {
        touch(TESTS_TMP_PATH . '/dir', 0);
        touch(TESTS_TMP_PATH . '/dir/subdir', 0);
        $this->assertTrue(FileSystem::directoryModifiedSince(TESTS_TMP_PATH . '/dir', 10));
    }

    public function testTouchUpdatesTheModificationTime(): void
    {
        touch(TESTS_TMP_PATH . '/sample.txt', 1_700_000_000);

        $this->assertTrue(FileSystem::touch(TESTS_TMP_PATH . '/sample.txt'));
        $this->assertGreaterThan(1_700_000_000, FileSystem::lastModifiedTime(TESTS_TMP_PATH . '/sample.txt'));
        $this->assertLessThanOrEqual(time(), FileSystem::lastModifiedTime(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testModeReturnsThePermissions(): void
    {
        chmod(TESTS_TMP_PATH . '/sample.txt', 0o640);
        clearstatcache();

        $mode = FileSystem::mode(TESTS_TMP_PATH . '/sample.txt');
        $this->assertSame(0o640, $mode & 0o777);

        chmod(TESTS_TMP_PATH . '/sample.txt', 0o600);
        clearstatcache();

        $mode = FileSystem::mode(TESTS_TMP_PATH . '/sample.txt');
        $this->assertSame(0o600, $mode & 0o777);
    }

    public function testSizeWorksForFilesAndDirectories(): void
    {
        $this->assertSame(49, FileSystem::size(TESTS_TMP_PATH . '/sample.txt'));
        $this->assertSame(196, FileSystem::size(TESTS_TMP_PATH . '/dir'));
    }

    public function testFileSizeReturnsSize(): void
    {
        $this->assertSame(49, FileSystem::fileSize(TESTS_TMP_PATH . '/sample.txt'));

        FileSystem::write(TESTS_TMP_PATH . '/empty.txt', '');
        $this->assertSame(0, FileSystem::fileSize(TESTS_TMP_PATH . '/empty.txt'));
    }

    public function testDirectorySizeReturnsTheSizeOfAllNestedFilesIncludingHiddenOnes(): void
    {
        $this->assertSame(196, FileSystem::directorySize(TESTS_TMP_PATH . '/dir'));

        FileSystem::write(TESTS_TMP_PATH . '/dir/subdir/extra.txt', 'twelve bytes');
        $this->assertSame(208, FileSystem::directorySize(TESTS_TMP_PATH . '/dir'));
    }

    public function testDeleteRemovesAFile(): void
    {
        $this->assertTrue(FileSystem::delete(TESTS_TMP_PATH . '/sample.txt'));
        $this->assertFileDoesNotExist(TESTS_TMP_PATH . '/sample.txt');
    }

    public function testDeleteRejectsUnsupportedTypes(): void
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

    public function testDeleteFileRemovesTheFile(): void
    {
        $this->assertTrue(FileSystem::deleteFile(TESTS_TMP_PATH . '/sample.txt'));
        $this->assertFileDoesNotExist(TESTS_TMP_PATH . '/sample.txt');
    }

    public function testDeleteDirectoryRemovesAnEmptyDirectory(): void
    {
        $this->assertTrue(FileSystem::deleteDirectory(TESTS_TMP_PATH . '/emptydir'));
        $this->assertDirectoryDoesNotExist(TESTS_TMP_PATH . '/emptydir');
    }

    public function testDeleteDirectoryCanRemoveTheContentToo(): void
    {
        $this->assertTrue(FileSystem::deleteDirectory(TESTS_TMP_PATH . '/dir', recursive: true));
        $this->assertDirectoryDoesNotExist(TESTS_TMP_PATH . '/dir');
    }

    public function testDeleteLinkPreservesTheTarget(): void
    {
        $this->assertTrue(FileSystem::deleteLink(TESTS_TMP_PATH . '/symlink'));
        $this->assertFalse(is_link(TESTS_TMP_PATH . '/symlink'));
        $this->assertFileExists(TESTS_TMP_PATH . '/sample.txt');
    }

    public function testCopyHandlesFilesDirectoriesAndLinks(): void
    {
        $this->assertTrue(FileSystem::copy(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_copy.txt'));

        $this->assertTrue(FileSystem::copy(TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir_copy'));

        $this->assertTrue(FileSystem::copy(TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/symlink_copy'));
    }

    public function testCopyFileDuplicatesTheContent(): void
    {
        $this->assertTrue(FileSystem::copyFile(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_copy.txt'));
        $this->assertFileEquals(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_copy.txt');
    }

    public function testCopyDirectoryDuplicatesTheContent(): void
    {
        $this->assertTrue(FileSystem::copyDirectory(TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir_copy'));
        $this->assertSame(FileSystem::directorySize(TESTS_TMP_PATH . '/dir'), FileSystem::directorySize(TESTS_TMP_PATH . '/dir_copy'));
    }

    #[DataProvider('ownSubtreeDestinationProvider')]
    public function testCopyDirectoryRejectsDestinationsInsideTheSource(string $destination): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Formwork\Utils\FileSystem::copyDirectory() cannot copy a directory into itself');
        FileSystem::copyDirectory(TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . $destination, overwrite: true);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function ownSubtreeDestinationProvider(): iterable
    {
        yield 'the source itself' => ['/dir'];
        yield 'a subdirectory of the source' => ['/dir/copy'];
        yield 'a path resolving inside the source' => ['/other/../dir/copy'];
    }

    public function testCopyDirectoryToADirectoryWithASimilarName(): void
    {
        $this->assertTrue(FileSystem::copyDirectory(TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir-copy'));
    }

    public function testCopyLinkPreservesTheTarget(): void
    {
        $this->assertTrue(FileSystem::copyLink(TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/symlink_copy'));
        $this->assertSame('sample.txt', readlink(TESTS_TMP_PATH . '/symlink_copy'));
    }

    public function testCopyLinkWithOverwrite(): void
    {
        $this->assertTrue(FileSystem::copyLink(TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/dir/b.txt', overwrite: true));
    }

    public function testMoveHandlesFilesDirectoriesAndLinks(): void
    {
        $this->assertTrue(FileSystem::move(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_moved.txt'));
        $this->assertFalse(FileSystem::exists(TESTS_TMP_PATH . '/sample.txt'));

        $this->assertTrue(FileSystem::move(TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir_moved'));
        $this->assertFalse(FileSystem::exists(TESTS_TMP_PATH . '/dir'));

        $this->assertTrue(FileSystem::move(TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/symlink_moved'));
        $this->assertFalse(FileSystem::exists(TESTS_TMP_PATH . '/symlink'));
    }

    public function testMoveFileRemovesTheSource(): void
    {
        $this->assertTrue(FileSystem::moveFile(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_moved.txt'));
        $this->assertFalse(FileSystem::exists(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testMoveDirectoryRemovesTheSource(): void
    {
        $this->assertTrue(FileSystem::moveDirectory(TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir_moved'));
        $this->assertFalse(FileSystem::exists(TESTS_TMP_PATH . '/dir'));
    }

    public function testMoveLinkRemovesTheSource(): void
    {
        $this->assertTrue(FileSystem::moveLink(TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/symlink_moved'));
        $this->assertFalse(FileSystem::exists(TESTS_TMP_PATH . '/symlink'));
    }

    public function testReadReturnsTheContent(): void
    {
        $this->assertSame("This is a sample text file for testing purposes.\n", FileSystem::read(TESTS_TMP_PATH . '/sample.txt'));
    }

    public function testReadRejectsUnreadableFiles(): void
    {
        $this->skipIfFilePermissionsAreNotEnforced();

        $mode = (int) fileperms(TESTS_TMP_PATH . '/sample.txt');
        chmod(TESTS_TMP_PATH . '/sample.txt', $mode & ~0o444);

        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('Cannot read "' . TESTS_TMP_PATH . '/sample.txt": file exists but is not readable');
        FileSystem::read(TESTS_TMP_PATH . '/sample.txt');

        chmod(TESTS_TMP_PATH . '/sample.txt', $mode);
    }

    public function testListContentsSkipsHiddenItems(): void
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

    public function testListRecursiveIncludesNestedItems(): void
    {
        $contents = iterator_to_array(FileSystem::listRecursive(TESTS_TMP_PATH));
        $this->assertContains('dir/subdir', $contents);
        $this->assertContains('dir/subdir/a.txt', $contents);
    }

    public function testListFilesSkipsDirectories(): void
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

    public function testListDirectoriesSkipsFiles(): void
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

    public function testReadLinkReturnsTheTarget(): void
    {
        $this->assertSame('sample.txt', FileSystem::readLink(TESTS_TMP_PATH . '/symlink'));
    }

    public function testCreateFileCreatesAnEmptyFile(): void
    {
        $this->assertTrue(FileSystem::createFile(TESTS_TMP_PATH . '/newfile.txt'));
        $this->assertSame('', FileSystem::read(TESTS_TMP_PATH . '/newfile.txt'));
    }

    public function testTemporaryFilesUseThePrefix(): void
    {
        $tempFile = FileSystem::createTemporaryFile(TESTS_TMP_PATH, '_tmp');
        $this->assertTrue(FileSystem::exists($tempFile));
        $this->assertStringStartsWith('_tmp', basename($tempFile));
    }

    public function testWriteCreatesAFileWithTheContent(): void
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

    public function testWriteRejectsUnwritableFiles(): void
    {
        $this->skipIfFilePermissionsAreNotEnforced();

        $mode = (int) fileperms(TESTS_TMP_PATH . '/sample.txt');
        chmod(TESTS_TMP_PATH . '/sample.txt', $mode & ~0o222);

        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('Cannot write "' . TESTS_TMP_PATH . '/sample.txt": file exists but is not writable');
        FileSystem::write(TESTS_TMP_PATH . '/sample.txt', 'Hello, World!');

        chmod(TESTS_TMP_PATH . '/sample.txt', $mode);
    }

    public function testWriteDoesNotLeaveTemporaryFiles(): void
    {
        FileSystem::createDirectory(TESTS_TMP_PATH . '/write');
        FileSystem::write(TESTS_TMP_PATH . '/write/newfile.txt', 'Hello, World!');

        $this->assertSame(['newfile.txt'], iterator_to_array(FileSystem::listContents(TESTS_TMP_PATH . '/write', FileSystem::LIST_ALL), false));
    }

    public function testFailedWritesRemoveTheTemporaryFile(): void
    {
        FileSystem::createDirectory(TESTS_TMP_PATH . '/write');

        foreach (['file_put_contents', 'rename'] as $function) {
            NativeFunctionsFixture::disable($function);

            $this->captureException(FileSystemException::class, static fn() => FileSystem::write(TESTS_TMP_PATH . '/write/newfile.txt', 'Hello, World!'));
            $this->assertSame([], iterator_to_array(FileSystem::listContents(TESTS_TMP_PATH . '/write', FileSystem::LIST_ALL), false));

            NativeFunctionsFixture::enable($function);
        }
    }

    public function testCreateDirectoryCreatesTheDirectory(): void
    {
        $this->assertTrue(FileSystem::createDirectory(TESTS_TMP_PATH . '/newdir'));
        $this->assertTrue(FileSystem::exists(TESTS_TMP_PATH . '/newdir'));
    }

    public function testCreateLinkCreatesASymbolicLink(): void
    {
        $this->assertTrue(FileSystem::createLink(TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/newlink'));
        $this->assertTrue(FileSystem::isLink(TESTS_TMP_PATH . '/newlink'));
    }

    public function testFormatSizeUsesTheLargestUnit(): void
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

    public function testShorthandNotationsAreConvertedToBytes(): void
    {
        $this->assertSame(1048576, FileSystem::shorthandToBytes('1M'));
        $this->assertSame(1073741824, FileSystem::shorthandToBytes('1G'));
        $this->assertSame(512, FileSystem::shorthandToBytes('512'));
        $this->assertSame(2048, FileSystem::shorthandToBytes('2K'));
    }

    public function testShorthandToBytesRejectsInvalidValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid shorthand bytes notation "1T"');
        FileSystem::shorthandToBytes('1T');
    }

    public function testRandomNamesAreUniqueAndSixteenCharactersLong(): void
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

    /**
     * @param array<string, mixed>|list<mixed> $arguments
     * @param class-string<Throwable>          $exception
     */
    #[DataProvider('invalidArgumentProvider')]
    public function testInvalidArgumentsAreRejected(string $method, array $arguments, string $exception, string $message): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $this->callFileSystem($method, $arguments);
    }

    /**
     * @return iterable<string, array{string, array<mixed>, class-string<Throwable>, string}>
     */
    public static function invalidArgumentProvider(): iterable
    {
        yield 'assert exists rejects missing paths' => ['assertExists', [TESTS_TMP_PATH . '/nonexistent.txt'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistent.txt" not found'];
        yield 'assert not exists rejects existing paths' => ['assertExists', [TESTS_TMP_PATH . '/sample.txt', false], FileSystemException::class, 'File "' . TESTS_TMP_PATH . '/sample.txt" already exists'];
        yield 'access time rejects missing files' => ['accessTime', [TESTS_TMP_PATH . '/nonexistent.txt'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistent.txt" not found'];
        yield 'creation time rejects missing files' => ['creationTime', [TESTS_TMP_PATH . '/nonexistent.txt'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistent.txt" not found'];
        yield 'last modified time rejects missing files' => ['lastModifiedTime', [TESTS_TMP_PATH . '/nonexistent.txt'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistent.txt" not found'];
        yield 'directory modified rejects non directories' => ['directoryModifiedSince', [TESTS_TMP_PATH . '/sample.txt', 0], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::directoryModifiedSince() accepts only directories as $directory argument'];
        yield 'touch rejects missing files' => ['touch', [TESTS_TMP_PATH . '/nonexistent.txt'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistent.txt" not found'];
        yield 'mode rejects missing files' => ['mode', [TESTS_TMP_PATH . '/nonexistent.txt'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistent.txt" not found'];
        yield 'size rejects unsupported types' => ['size', ['/dev/null'], FileSystemException::class, 'unsupported file type'];
        yield 'file size rejects non files' => ['fileSize', [TESTS_TMP_PATH . '/dir'], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::fileSize() accepts only files as $file argument'];
        yield 'file size rejects missing files' => ['fileSize', [TESTS_TMP_PATH . '/nonexistent.txt'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistent.txt" not found'];
        yield 'directory size rejects non directories' => ['directorySize', [TESTS_TMP_PATH . '/sample.txt'], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::directorySize() accepts only directories as $directory argument'];
        yield 'directory size rejects missing directories' => ['directorySize', [TESTS_TMP_PATH . '/nonexistentdir'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistentdir" not found'];
        yield 'delete file rejects non files' => ['deleteFile', [TESTS_TMP_PATH . '/dir'], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::deleteFile() accepts only files as $file argument'];
        yield 'delete file rejects missing files' => ['deleteFile', [TESTS_TMP_PATH . '/nonexistent.txt'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistent.txt" not found'];
        yield 'delete directory rejects non empty directories' => ['deleteDirectory', [TESTS_TMP_PATH . '/dir'], FileSystemException::class, 'must be empty to be deleted'];
        yield 'delete directory rejects non directories' => ['deleteDirectory', [TESTS_TMP_PATH . '/sample.txt'], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::deleteDirectory() accepts only directories as $directory argument'];
        yield 'delete directory rejects missing directories' => ['deleteDirectory', [TESTS_TMP_PATH . '/nonexistentdir'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistentdir" not found'];
        yield 'delete link rejects non links' => ['deleteLink', [TESTS_TMP_PATH . '/sample.txt'], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::deleteLink() accepts only links as $link argument'];
        yield 'delete link rejects missing links' => ['deleteLink', [TESTS_TMP_PATH . '/nonexistentlink'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistentlink" not found'];
        yield 'copy rejects unsupported types' => ['copy', ['/dev/null', TESTS_TMP_PATH . '/null_copy'], FileSystemException::class, 'unsupported file type'];
        yield 'copy file rejects non files' => ['copyFile', [TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir_copy'], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::copyFile() accepts only files as $source argument'];
        yield 'copy file rejects missing files' => ['copyFile', [TESTS_TMP_PATH . '/nonexistent.txt', TESTS_TMP_PATH . '/nonexistent_copy.txt'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistent.txt" not found'];
        yield 'copy file rejects existing destinations' => ['copyFile', [TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample.txt'], FileSystemException::class, 'File "' . TESTS_TMP_PATH . '/sample.txt" already exists'];
        yield 'copy directory rejects non directories' => ['copyDirectory', [TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_copy.txt'], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::copyDirectory() accepts only directories as $source argument'];
        yield 'copy directory rejects missing directories' => ['copyDirectory', [TESTS_TMP_PATH . '/nonexistentdir', TESTS_TMP_PATH . '/nonexistentdir_copy'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistentdir" not found'];
        yield 'copy directory rejects existing destinations' => ['copyDirectory', [TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir'], FileSystemException::class, 'Directory "' . TESTS_TMP_PATH . '/dir" already exists'];
        yield 'copy link rejects non links' => ['copyLink', [TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_link'], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::copyLink() accepts only links as $source argument'];
        yield 'copy link rejects missing links' => ['copyLink', [TESTS_TMP_PATH . '/nonexistentlink', TESTS_TMP_PATH . '/nonexistentlink_copy'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistentlink" not found'];
        yield 'copy link rejects existing destinations' => ['copyLink', [TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/symlink'], FileSystemException::class, 'File "' . TESTS_TMP_PATH . '/symlink" already exists'];
        yield 'move rejects unsupported types' => ['move', ['/dev/null', TESTS_TMP_PATH . '/null_moved'], FileSystemException::class, 'unsupported file type'];
        yield 'move file rejects non files' => ['moveFile', [TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir_moved'], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::moveFile() accepts only files as $source argument'];
        yield 'move file rejects missing files' => ['moveFile', [TESTS_TMP_PATH . '/nonexistent.txt', TESTS_TMP_PATH . '/nonexistent_moved.txt'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistent.txt" not found'];
        yield 'move file rejects existing destinations' => ['moveFile', [TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample.txt'], FileSystemException::class, 'File "' . TESTS_TMP_PATH . '/sample.txt" already exists'];
        yield 'move directory rejects non directories' => ['moveDirectory', [TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_moved.txt'], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::moveDirectory() accepts only directories as $source argument'];
        yield 'move directory rejects missing directories' => ['moveDirectory', [TESTS_TMP_PATH . '/nonexistentdir', TESTS_TMP_PATH . '/nonexistentdir_moved'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistentdir" not found'];
        yield 'move directory rejects existing destinations' => ['moveDirectory', [TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir'], FileSystemException::class, 'Directory "' . TESTS_TMP_PATH . '/dir" already exists'];
        yield 'move directory rejects destinations inside the source' => ['moveDirectory', [TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir/moved'], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::copyDirectory() cannot copy a directory into itself'];
        yield 'move link rejects non links' => ['moveLink', [TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_moved'], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::moveLink() accepts only links as $source argument'];
        yield 'move link rejects missing links' => ['moveLink', [TESTS_TMP_PATH . '/nonexistentlink', TESTS_TMP_PATH . '/nonexistentlink_moved'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistentlink" not found'];
        yield 'move link rejects existing destinations' => ['moveLink', [TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/symlink'], FileSystemException::class, 'File "' . TESTS_TMP_PATH . '/symlink" already exists'];
        yield 'read rejects non files' => ['read', [TESTS_TMP_PATH . '/dir'], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::read() accepts only files as $file argument'];
        yield 'read rejects missing files' => ['read', [TESTS_TMP_PATH . '/nonexistent.txt'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistent.txt" not found'];
        yield 'list contents rejects non directories' => ['listContents', [TESTS_TMP_PATH . '/sample.txt'], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::listContents() accepts only directories as $directory argument'];
        yield 'list contents rejects missing directories' => ['listContents', [TESTS_TMP_PATH . '/nonexistentdir'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistentdir" not found'];
        yield 'list recursive rejects non directories' => ['listRecursive', [TESTS_TMP_PATH . '/sample.txt'], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::listRecursive() accepts only directories as $directory argument'];
        yield 'read link rejects non links' => ['readLink', [TESTS_TMP_PATH . '/sample.txt'], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::readLink() accepts only links as $link argument'];
        yield 'read link rejects missing links' => ['readLink', [TESTS_TMP_PATH . '/nonexistentlink'], FileNotFoundException::class, 'File "' . TESTS_TMP_PATH . '/nonexistentlink" not found'];
        yield 'write rejects non files' => ['write', [TESTS_TMP_PATH . '/dir', 'Hello, World!'], InvalidArgumentException::class, 'Formwork\Utils\FileSystem::write() accepts only files as $file argument'];
    }

    /**
     * @param array<string, mixed>|list<mixed> $arguments
     */
    #[DataProvider('systemErrorProvider')]
    public function testSystemErrorsAreReported(string $disabledFunction, string $method, array $arguments, string $message): void
    {
        NativeFunctionsFixture::disable($disabledFunction);

        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage($message);

        $this->callFileSystem($method, $arguments);
    }

    /**
     * @return iterable<string, array{string, string, array<mixed>, string}>
     */
    public static function systemErrorProvider(): iterable
    {
        yield 'access time' => ['fileatime', 'accessTime', [TESTS_TMP_PATH . '/dir/sample.txt'], 'Cannot get access time of "' . TESTS_TMP_PATH . '/dir/sample.txt": '];
        yield 'creation time' => ['filectime', 'creationTime', [TESTS_TMP_PATH . '/dir/sample.txt'], 'Cannot get creation time of "' . TESTS_TMP_PATH . '/dir/sample.txt": '];
        yield 'last modified time' => ['filemtime', 'lastModifiedTime', [TESTS_TMP_PATH . '/dir/sample.txt'], 'Cannot get last modified time of "' . TESTS_TMP_PATH . '/dir/sample.txt": '];
        yield 'touch' => ['touch', 'touch', [TESTS_TMP_PATH . '/sample.txt'], 'Cannot touch "' . TESTS_TMP_PATH . '/sample.txt": '];
        yield 'mode' => ['fileperms', 'mode', [TESTS_TMP_PATH . '/sample.txt'], 'Cannot get permissions of "' . TESTS_TMP_PATH . '/sample.txt": '];
        yield 'file size' => ['filesize', 'fileSize', [TESTS_TMP_PATH . '/sample.txt'], 'Cannot get file size for "' . TESTS_TMP_PATH . '/sample.txt": '];
        yield 'directory size' => ['filesize', 'directorySize', [TESTS_TMP_PATH . '/dir'], 'Cannot get file size for "' . TESTS_TMP_PATH . '/dir/.hidden": '];
        yield 'delete file' => ['unlink', 'deleteFile', [TESTS_TMP_PATH . '/sample.txt'], 'Cannot delete file "' . TESTS_TMP_PATH . '/sample.txt": '];
        yield 'delete directory' => ['rmdir', 'deleteDirectory', [TESTS_TMP_PATH . '/emptydir'], 'Cannot delete directory "' . TESTS_TMP_PATH . '/emptydir": '];
        yield 'delete link' => ['unlink', 'deleteLink', [TESTS_TMP_PATH . '/symlink'], 'Cannot delete symbolic link "' . TESTS_TMP_PATH . '/symlink": '];
        yield 'copy file' => ['copy', 'copyFile', [TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_copy.txt'], 'Cannot copy file "' . TESTS_TMP_PATH . '/sample.txt": '];
        yield 'copy directory' => ['copy', 'copyDirectory', [TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir_copy'], 'Cannot copy file "' . TESTS_TMP_PATH . '/dir/.hidden": '];
        yield 'copy link' => ['symlink', 'copyLink', [TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/symlink_copy'], 'Cannot create symbolic link "' . TESTS_TMP_PATH . '/symlink_copy": '];
        yield 'move file' => ['rename', 'moveFile', [TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/sample_moved.txt'], 'Cannot move file "' . TESTS_TMP_PATH . '/sample.txt": '];
        yield 'move directory' => ['copy', 'moveDirectory', [TESTS_TMP_PATH . '/dir', TESTS_TMP_PATH . '/dir_moved'], 'Cannot copy file "' . TESTS_TMP_PATH . '/dir/.hidden": '];
        yield 'move link' => ['symlink', 'moveLink', [TESTS_TMP_PATH . '/symlink', TESTS_TMP_PATH . '/symlink_moved'], 'Cannot create symbolic link "' . TESTS_TMP_PATH . '/symlink_moved": '];
        yield 'read' => ['file_get_contents', 'read', [TESTS_TMP_PATH . '/sample.txt'], 'Cannot read "' . TESTS_TMP_PATH . '/sample.txt": '];
        yield 'list contents' => ['opendir', 'listContents', [TESTS_TMP_PATH . '/dir'], 'Cannot open the directory "' . TESTS_TMP_PATH . '/dir": '];
        yield 'read link' => ['readlink', 'readLink', [TESTS_TMP_PATH . '/symlink'], 'Cannot resolve symbolic link "' . TESTS_TMP_PATH . '/symlink": '];
        yield 'create file' => ['fopen', 'createFile', [TESTS_TMP_PATH . '/newfile.txt'], 'Cannot create file "' . TESTS_TMP_PATH . '/newfile.txt": '];
        yield 'create temp file' => ['fopen', 'createTemporaryFile', [TESTS_TMP_PATH, '_tmp'], 'Cannot create a temporary file'];
        yield 'write' => ['file_put_contents', 'write', [TESTS_TMP_PATH . '/newfile.txt', 'Hello, World!'], 'Cannot write "' . TESTS_TMP_PATH . '/newfile.txt": '];
        yield 'create directory' => ['mkdir', 'createDirectory', [TESTS_TMP_PATH . '/newdir'], 'Cannot create directory "' . TESTS_TMP_PATH . '/newdir": '];
        yield 'create link' => ['symlink', 'createLink', [TESTS_TMP_PATH . '/sample.txt', TESTS_TMP_PATH . '/newlink'], 'Cannot create symbolic link "' . TESTS_TMP_PATH . '/newlink": '];
    }

    /**
     * The root user bypasses file permission checks, so tests relying on them cannot work
     */
    private function skipIfFilePermissionsAreNotEnforced(): void
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('File permissions are not enforced for the root user.');
        }
    }

    /**
     * @param array<string, mixed>|list<mixed> $arguments
     */
    private function callFileSystem(string $method, array $arguments): void
    {
        $result = FileSystem::{$method}(...$arguments);

        // Listing methods are generators, which run only when iterated
        if ($result instanceof Generator) {
            iterator_to_array($result);
        }
    }
}
