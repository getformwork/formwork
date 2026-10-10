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
        $files = new FilesData(['avatar' => $this->file('avatar', UPLOAD_ERR_NO_FILE)]);

        $this->assertTrue($files->isEmpty());
    }

    public function testUploadedFilesAreNotEmpty(): void
    {
        $files = new FilesData(['avatar' => $this->file('avatar', UPLOAD_ERR_OK)]);

        $this->assertFalse($files->isEmpty());
    }

    public function testFailedUploadsAreNotConsideredEmpty(): void
    {
        $files = new FilesData(['avatar' => $this->file('avatar', UPLOAD_ERR_INI_SIZE)]);

        $this->assertFalse($files->isEmpty(), 'Oversized uploads must still be reported to the user');
    }

    public function testASingleUploadedFileAmongEmptyOnesMakesTheDataNotEmpty(): void
    {
        $files = new FilesData([
            'first'  => $this->file('first', UPLOAD_ERR_NO_FILE),
            'second' => $this->file('second', UPLOAD_ERR_OK),
        ]);

        $this->assertFalse($files->isEmpty());
    }

    public function testNestedFilesAreFlattened(): void
    {
        $first = $this->file('gallery', UPLOAD_ERR_OK);
        $second = $this->file('gallery', UPLOAD_ERR_NO_FILE);
        $third = $this->file('other', UPLOAD_ERR_OK);
        $files = new FilesData(['gallery' => [$first, $second], 'other' => $third]);

        $this->assertSame([$first, $second, $third], $files->getAll());
    }

    public function testNestedEmptyFilesAreEmpty(): void
    {
        $files = new FilesData(['gallery' => [$this->file('gallery', UPLOAD_ERR_NO_FILE), $this->file('gallery', UPLOAD_ERR_NO_FILE)]]);

        $this->assertTrue($files->isEmpty());
    }

    public function testDeeplyNestedUploadsAreFound(): void
    {
        $uploaded = $this->file('deep', UPLOAD_ERR_OK);
        $files = new FilesData(['a' => ['b' => ['c' => $uploaded]]]);

        $this->assertFalse($files->isEmpty());
        $this->assertSame([$uploaded], $files->getAll());
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
