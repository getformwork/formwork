<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Files\UploadedFile;
use Formwork\Http\FilesData;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FilesData::class)]
final class FilesDataTest extends TestCase
{
    public function testEmptyDataIsEmpty(): void
    {
        $this->assertTrue((new FilesData([]))->isEmpty());
        $this->assertSame([], (new FilesData([]))->getAll());
    }

    public function testFilesWithoutAnUploadAreEmpty(): void
    {
        $filesData = new FilesData(['avatar' => $this->file('avatar', UPLOAD_ERR_NO_FILE)]);

        $this->assertTrue($filesData->isEmpty());
    }

    public function testUploadedFilesAreNotEmpty(): void
    {
        $filesData = new FilesData(['avatar' => $this->file('avatar', UPLOAD_ERR_OK)]);

        $this->assertFalse($filesData->isEmpty());
    }

    public function testFailedUploadsAreNotConsideredEmpty(): void
    {
        $filesData = new FilesData(['avatar' => $this->file('avatar', UPLOAD_ERR_INI_SIZE)]);

        $this->assertFalse($filesData->isEmpty(), 'Oversized uploads must still be reported to the user');
    }

    public function testASingleUploadedFileAmongEmptyOnesMakesTheDataNotEmpty(): void
    {
        $filesData = new FilesData([
            'first'  => $this->file('first', UPLOAD_ERR_NO_FILE),
            'second' => $this->file('second', UPLOAD_ERR_OK),
        ]);

        $this->assertFalse($filesData->isEmpty());
    }

    public function testNestedFilesAreFlattened(): void
    {
        $uploadedFile = $this->file('gallery', UPLOAD_ERR_OK);
        $second = $this->file('gallery', UPLOAD_ERR_NO_FILE);
        $third = $this->file('other', UPLOAD_ERR_OK);
        $filesData = new FilesData(['gallery' => [$uploadedFile, $second], 'other' => $third]);

        $this->assertSame([$uploadedFile, $second, $third], $filesData->getAll());
    }

    public function testNestedEmptyFilesAreEmpty(): void
    {
        $filesData = new FilesData(['gallery' => [$this->file('gallery', UPLOAD_ERR_NO_FILE), $this->file('gallery', UPLOAD_ERR_NO_FILE)]]);

        $this->assertTrue($filesData->isEmpty());
    }

    public function testDeeplyNestedUploadsAreFound(): void
    {
        $uploadedFile = $this->file('deep', UPLOAD_ERR_OK);
        $filesData = new FilesData(['a' => ['b' => ['c' => $uploadedFile]]]);

        $this->assertFalse($filesData->isEmpty());
        $this->assertSame([$uploadedFile], $filesData->getAll());
    }

    private function file(string $field, int $error): UploadedFile
    {
        return new UploadedFile($field, [
            'name'      => 'file.txt',
            'full_path' => 'file.txt',
            'type'      => 'text/plain',
            'tmp_name'  => '/tmp/php-upload',
            'error'     => (string) $error,
            'size'      => '0',
        ]);
    }
}
