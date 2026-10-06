<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Exceptions\TranslatedException;
use Formwork\Http\Files\UploadedFile;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

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

    public function testUploadErrorsExposeTranslatedMessagesAndBlockMove(): void
    {
        $file = new UploadedFile('document', [
            'name'     => 'document.txt', 'full_path' => 'document.txt', 'type' => 'text/plain',
            'tmp_name' => '/tmp/php-document', 'error' => (string) UPLOAD_ERR_NO_FILE, 'size' => '0',
        ]);

        $this->assertTrue($file->isEmpty());
        $this->assertSame('No file was uploaded', $file->getErrorMessage());
        $this->assertSame('upload.error.noFile', $file->getErrorTranslationString());
        $this->expectException(TranslatedException::class);
        $file->move(__DIR__ . '/fixtures/files', 'document.txt');
    }
}
