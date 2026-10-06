<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\FileResponse;
use Formwork\Http\Request;
use Formwork\Http\ResponseStatus;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FileResponse::class)]
final class FileResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testFileResponsePreparesRangesAndDownloadNames(): void
    {
        $path = $this->fixturePath();
        $response = new FileResponse($path, download: true, autoEtag: true, autoLastModified: true);
        $response->prepare($this->request(['HTTP_RANGE' => 'bytes=0-4']));

        $this->assertSame(ResponseStatus::PartialContent, $response->status());
        $this->assertSame('bytes 0-4/' . FileSystem::fileSize($path), $response->headers()->get('Content-Range'));
        $this->assertSame('5', $response->headers()->get('Content-Length'));
        $this->assertTrue($response->headers()->has('ETag'));
        $this->assertTrue($response->headers()->has('Last-Modified'));
        $this->assertSame($response, $response->setFilename('download.txt'));
        $this->assertSame('attachment; filename=download.txt', $response->headers()->get('Content-Disposition'));
    }

    public function testFileResponseHandlesSuffixOpenEndedAndUnsatisfiableRanges(): void
    {
        $path = $this->fixturePath();
        $size = FileSystem::fileSize($path);

        $suffix = new FileResponse($path);
        $suffix->prepare($this->request(['HTTP_RANGE' => 'bytes=-5']));
        $this->assertSame('bytes ' . ($size - 5) . '-' . ($size - 1) . '/' . $size, $suffix->headers()->get('Content-Range'));

        $openEnded = new FileResponse($path);
        $openEnded->prepare($this->request(['HTTP_RANGE' => 'bytes=3-']));
        $this->assertSame('bytes 3-' . ($size - 1) . '/' . $size, $openEnded->headers()->get('Content-Range'));

        $invalid = new FileResponse($path);
        $invalid->prepare($this->request(['HTTP_RANGE' => 'bytes=999-1000']));
        $this->assertSame(ResponseStatus::RangeNotSatisfiable, $invalid->status());
        $this->assertSame('bytes */' . $size, $invalid->headers()->get('Content-Range'));
        $this->assertSame('0', $invalid->headers()->get('Content-Length'));
    }

    public function testFileResponseAddsAcceptRangesForHeadAndSkipsRangesForEmptyResponses(): void
    {
        $path = $this->fixturePath();
        $head = new FileResponse($path);
        $head->prepare($this->request(['REQUEST_METHOD' => 'HEAD']));
        $empty = new FileResponse($path, ResponseStatus::NoContent);
        $empty->prepare($this->request());

        $this->assertSame('bytes', $head->headers()->get('Accept-Ranges'));
        $this->assertFalse($empty->headers()->has('Accept-Ranges'));
    }

    public function testFileResponseSendStreamsFullContent(): void
    {
        $response = new FileResponse($this->fixturePath());
        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertSame("Formwork HTTP fixture content.\n", $output);
    }

    public function testFileResponseSendStreamsPartialContent(): void
    {
        $response = new FileResponse($this->fixturePath());
        $response->prepare($this->request(['HTTP_RANGE' => 'bytes=0-4']));
        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertSame('Formw', $output);
    }

    public function testHeadResponseDoesNotStreamOrDeleteTheFile(): void
    {
        $path = FileSystem::joinPaths(TESTS_TMP_PATH, 'head.txt');
        FileSystem::copyFile($this->fixturePath(), $path);
        $response = new FileResponse($path, deleteAfterSend: true);
        $response->prepare($this->request(['REQUEST_METHOD' => 'HEAD']));
        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertSame('', $output);
        $this->assertTrue(FileSystem::exists($path));
    }

    public function testInlineFileResponseRejectsDownloadFilenameChanges(): void
    {
        $response = new FileResponse($this->fixturePath());

        $this->expectException(LogicException::class);
        $response->setFilename('download.txt');
    }

    private function fixturePath(): string
    {
        return __DIR__ . '/fixtures/files/sample.txt';
    }

    /**
     * @param array<string, string> $server
     */
    private function request(array $server = []): Request
    {
        return new Request([], [], [], [], $server + [
            'REQUEST_METHOD' => 'GET', 'SERVER_NAME' => 'example.test', 'SERVER_PORT' => '80',
        ]);
    }
}
