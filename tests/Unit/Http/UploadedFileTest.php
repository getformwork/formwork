<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Exceptions\TranslatedException;
use Formwork\Http\Files\UploadedFile;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(UploadedFile::class)]
final class UploadedFileTest extends TestCase
{
    public function testUploadedFileExposesClientAndUploadMetadata(): void
    {
        $file = new UploadedFile('avatar', [
            'name'     => 'avatar.png', 'full_path' => 'pictures/avatar.png', 'type' => 'image/png',
            'tmp_name' => '/tmp/php-avatar', 'error' => (string) UPLOAD_ERR_OK, 'size' => '12',
        ]);

        $this->assertSame('avatar', $file->fieldName());
        $this->assertSame('avatar.png', $file->clientName());
        $this->assertSame('pictures/avatar.png', $file->clientFullPath());
        $this->assertSame('image/png', $file->clientMimeType());
        $this->assertSame('/tmp/php-avatar', $file->tempPath());
        $this->assertSame(UPLOAD_ERR_OK, $file->error());
        $this->assertSame(12, $file->size());
        $this->assertTrue($file->isUploaded());
        $this->assertFalse($file->isEmpty());
    }

    #[DataProvider('uploadErrorProvider')]
    public function testEveryUploadErrorHasAMessageATranslationAndBlocksTheMove(int $error, string $message, string $translation): void
    {
        $file = $this->uploadedFile($error);

        $this->assertSame($message, $file->getErrorMessage());
        $this->assertSame($translation, $file->getErrorTranslationString());
        $this->assertFalse($file->isUploaded());
        $this->assertSame($error === UPLOAD_ERR_NO_FILE, $file->isEmpty());

        try {
            $file->move(TESTS_TMP_PATH, 'file.txt');
            $this->fail('A file with an upload error was moved.');
        } catch (TranslatedException $exception) {
            $this->assertSame($translation, $exception->getLanguageString());
            $this->assertStringContainsString($message, $exception->getMessage());
        }
    }

    /**
     * @return iterable<string, array{int, string, string}>
     */
    public static function uploadErrorProvider(): iterable
    {
        yield 'ini size' => [UPLOAD_ERR_INI_SIZE, 'The uploaded file exceeds the upload_max_filesize directive in php.ini', 'upload.error.size'];
        yield 'form size' => [UPLOAD_ERR_FORM_SIZE, 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form', 'upload.error.size'];
        yield 'partial' => [UPLOAD_ERR_PARTIAL, 'The uploaded file was only partially uploaded', 'upload.error.partial'];
        yield 'no file' => [UPLOAD_ERR_NO_FILE, 'No file was uploaded', 'upload.error.noFile'];
        yield 'no temporary directory' => [UPLOAD_ERR_NO_TMP_DIR, 'Missing a temporary folder', 'upload.error.noTemp'];
        yield 'cannot write' => [UPLOAD_ERR_CANT_WRITE, 'Failed to write file to disk', 'upload.error.cannotWrite'];
        yield 'extension' => [UPLOAD_ERR_EXTENSION, 'A Php extension stopped the file upload', 'upload.error.phpExtension'];
    }

    public function testMoveRejectsTooLongFileNames(): void
    {
        $this->expectException(TranslatedException::class);
        $this->expectExceptionMessage('File name too long');

        $this->uploadedFile(UPLOAD_ERR_OK)->move(TESTS_TMP_PATH, str_repeat('a', FileSystem::MAX_NAME_LENGTH + 1));
    }

    public function testMoveAcceptsFileNamesOfTheMaximumLength(): void
    {
        try {
            $this->uploadedFile(UPLOAD_ERR_OK)->move(TESTS_TMP_PATH, str_repeat('a', FileSystem::MAX_NAME_LENGTH));
            $this->fail('A file that was not uploaded through HTTP was moved.');
        } catch (TranslatedException $exception) {
            // The name is accepted, then PHP refuses to move a file that was not uploaded through HTTP
            $this->assertSame('upload.error.cannotMoveToDestination', $exception->getLanguageString());
        }
    }

    public function testMoveRejectsTooLongDestinationPaths(): void
    {
        $this->expectException(TranslatedException::class);
        $this->expectExceptionMessage('Destination path too long');

        $this->uploadedFile(UPLOAD_ERR_OK)->move(str_repeat('a/', (int) (FileSystem::MAX_PATH_LENGTH / 2)), 'file.txt');
    }

    public function testMoveRefusesToReplaceAnExistingFileUnlessOverwriteIsEnabled(): void
    {
        $this->setUpTempDirectory();

        try {
            FileSystem::write(TESTS_TMP_PATH . '/existing.txt', 'existing');
            $file = $this->uploadedFile(UPLOAD_ERR_OK);

            try {
                $file->move(TESTS_TMP_PATH, 'existing.txt');
                $this->fail('An existing file was replaced without overwrite.');
            } catch (TranslatedException $exception) {
                $this->assertSame('upload.error.alreadyExists', $exception->getLanguageString());
            }

            // Not an HTTP upload, so PHP refuses the move after the checks above have passed
            try {
                $file->move(TESTS_TMP_PATH, 'existing.txt', overwrite: true);
                $this->fail('A file that was not uploaded through HTTP was moved.');
            } catch (TranslatedException $exception) {
                $this->assertSame('upload.error.cannotMoveToDestination', $exception->getLanguageString());
            }

            $this->assertSame('existing', FileSystem::read(TESTS_TMP_PATH . '/existing.txt'));
        } finally {
            $this->tearDownTempDirectory();
        }
    }

    private function uploadedFile(int $error): UploadedFile
    {
        return new UploadedFile('upload', [
            'name'      => 'file.txt',
            'full_path' => 'file.txt',
            'type'      => 'text/plain',
            'tmp_name'  => TESTS_TMP_PATH . '/php-upload',
            'error'     => (string) $error,
            'size'      => '0',
        ]);
    }
}
