<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Exceptions\TranslatedException;
use Formwork\Http\Files\UploadedFile;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(UploadedFile::class)]
#[UsesTempDirectory]
final class UploadedFileTest extends TestCase
{
    public function testUploadedFileExposesClientAndUploadMetadata(): void
    {
        $uploadedFile = new UploadedFile('avatar', [
            'name'     => 'avatar.png', 'full_path' => 'pictures/avatar.png', 'type' => 'image/png',
            'tmp_name' => '/tmp/php-avatar', 'error' => (string) UPLOAD_ERR_OK, 'size' => '12',
        ]);

        $this->assertSame('avatar', $uploadedFile->fieldName());
        $this->assertSame('avatar.png', $uploadedFile->clientName());
        $this->assertSame('pictures/avatar.png', $uploadedFile->clientFullPath());
        $this->assertSame('image/png', $uploadedFile->clientMimeType());
        $this->assertSame('/tmp/php-avatar', $uploadedFile->tempPath());
        $this->assertSame(UPLOAD_ERR_OK, $uploadedFile->error());
        $this->assertSame(12, $uploadedFile->size());
        $this->assertTrue($uploadedFile->isUploaded());
        $this->assertFalse($uploadedFile->isEmpty());
    }

    #[DataProvider('uploadErrorProvider')]
    public function testEveryUploadErrorHasAMessageATranslationAndBlocksTheMove(int $error, string $message, string $translation): void
    {
        $uploadedFile = $this->uploadedFile($error);

        $this->assertSame($message, $uploadedFile->getErrorMessage());
        $this->assertSame($translation, $uploadedFile->getErrorTranslationString());
        $this->assertFalse($uploadedFile->isUploaded());
        $this->assertSame($error === UPLOAD_ERR_NO_FILE, $uploadedFile->isEmpty());

        $translatedException = $this->captureException(TranslatedException::class, static fn() => $uploadedFile->move(TESTS_TMP_PATH, 'file.txt'));
        $this->assertSame($translation, $translatedException->getLanguageString());
        $this->assertStringContainsString($message, $translatedException->getMessage());
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

    public function testMoveRejectsTooLongDestinationPaths(): void
    {
        $this->expectException(TranslatedException::class);
        $this->expectExceptionMessage('Destination path too long');

        $this->uploadedFile(UPLOAD_ERR_OK)->move(str_repeat('a/', (int) (FileSystem::MAX_PATH_LENGTH / 2)), 'file.txt');
    }

    public function testMoveAcceptsFileNamesOfTheMaximumLength(): void
    {
        $translatedException = $this->captureException(TranslatedException::class, fn() => $this->uploadedFile(UPLOAD_ERR_OK)->move(TESTS_TMP_PATH, str_repeat('a', FileSystem::MAX_NAME_LENGTH)));
        // The name is accepted, then PHP refuses to move a file that was not uploaded through HTTP
        $this->assertSame('upload.error.cannotMoveToDestination', $translatedException->getLanguageString());
    }

    public function testMoveRefusesToReplaceAnExistingFileUnlessOverwriteIsEnabled(): void
    {
        FileSystem::write(TESTS_TMP_PATH . '/existing.txt', 'existing');
        $uploadedFile = $this->uploadedFile(UPLOAD_ERR_OK);

        $exception = $this->captureException(TranslatedException::class, static fn() => $uploadedFile->move(TESTS_TMP_PATH, 'existing.txt'));
        $this->assertSame('upload.error.alreadyExists', $exception->getLanguageString());

        // Not an HTTP upload, so PHP refuses the move after the checks above have passed
        $exception = $this->captureException(TranslatedException::class, static fn() => $uploadedFile->move(TESTS_TMP_PATH, 'existing.txt', overwrite: true));
        $this->assertSame('upload.error.cannotMoveToDestination', $exception->getLanguageString());

        $this->assertSame('existing', FileSystem::read(TESTS_TMP_PATH . '/existing.txt'));
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
