<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\FileResponse;
use Formwork\Http\Request;
use Formwork\Http\RequestMethod;
use Formwork\Http\ResponseStatus;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(FileResponse::class)]
#[UsesTempDirectory]
final class FileResponseTest extends TestCase
{
    public function testFileResponsePreparesRangesAndDownloadNames(): void
    {
        $path = $this->fixturePath();
        $fileResponse = new FileResponse($path, download: true, autoEtag: true, autoLastModified: true);
        $fileResponse->prepare($this->request(server: ['HTTP_RANGE' => 'bytes=0-4']));

        $this->assertSame(ResponseStatus::PartialContent, $fileResponse->status());
        $this->assertSame('bytes 0-4/' . FileSystem::fileSize($path), $fileResponse->headers()->get('Content-Range'));
        $this->assertSame('5', $fileResponse->headers()->get('Content-Length'));
        $this->assertMatchesRegularExpression('/^W\/"[0-9a-f]{64}"$/', (string) $fileResponse->headers()->get('ETag'));
        $this->assertTrue($fileResponse->headers()->has('Last-Modified'));
        $this->assertSame($fileResponse, $fileResponse->setFilename('download.txt'));
        $this->assertSame('attachment; filename=download.txt', $fileResponse->headers()->get('Content-Disposition'));
    }

    public function testFileResponseAddsAcceptRangesForHeadAndSkipsRangesForEmptyResponses(): void
    {
        $path = $this->fixturePath();
        $head = new FileResponse($path);
        $head->prepare($this->request(RequestMethod::HEAD));
        $empty = new FileResponse($path, ResponseStatus::NoContent);
        $empty->prepare($this->request());

        $this->assertSame('bytes', $head->headers()->get('Accept-Ranges'));
        $this->assertFalse($empty->headers()->has('Accept-Ranges'));
    }

    #[DataProvider('satisfiableRangeProvider')]
    public function testFileResponseServesSatisfiableRangesWithTheExactBoundaries(string $range, int $start, int $end): void
    {
        $path = $this->fixturePath();
        $size = FileSystem::fileSize($path);
        $fileResponse = new FileResponse($path);
        $fileResponse->prepare($this->request(server: ['HTTP_RANGE' => $range]));

        $this->assertSame(ResponseStatus::PartialContent, $fileResponse->status());
        $this->assertSame(sprintf('bytes %d-%d/%d', $start, $end, $size), $fileResponse->headers()->get('Content-Range'));
        $this->assertSame((string) ($end - $start + 1), $fileResponse->headers()->get('Content-Length'));
    }

    /**
     * The fixture is 31 bytes long
     *
     * @return iterable<string, array{string, int, int}>
     */
    public static function satisfiableRangeProvider(): iterable
    {
        yield 'single first byte' => ['bytes=0-0', 0, 0];
        yield 'single byte in the middle' => ['bytes=3-3', 3, 3];
        yield 'single last byte' => ['bytes=30-30', 30, 30];
        yield 'open ended range' => ['bytes=3-', 3, 30];
        yield 'suffix range' => ['bytes=-5', 26, 30];
        yield 'last byte by suffix' => ['bytes=-1', 30, 30];
        yield 'whole file' => ['bytes=0-30', 0, 30];
        yield 'end beyond the file is clamped' => ['bytes=5-999', 5, 30];
        yield 'suffix longer than the file is clamped' => ['bytes=-999', 0, 30];
    }

    public function testFileResponseRejectsRangesStartingAfterTheirEndOrAfterTheFile(): void
    {
        $size = FileSystem::fileSize($this->fixturePath());

        foreach (['bytes=5-4', 'bytes=' . $size . '-' . $size, 'bytes=' . $size . '-'] as $range) {
            $response = new FileResponse($this->fixturePath());
            $response->prepare($this->request(server: ['HTTP_RANGE' => $range]));

            $this->assertSame(ResponseStatus::RangeNotSatisfiable, $response->status(), $range);
            $this->assertSame('bytes */' . $size, $response->headers()->get('Content-Range'), $range);
            $this->assertSame('0', $response->headers()->get('Content-Length'), $range);
        }
    }

    public function testFileResponseIgnoresMalformedRanges(): void
    {
        foreach (['bytes=-', 'items=0-4', 'bytes=a-b', 'bytes=0-4,6-8'] as $range) {
            $response = new FileResponse($this->fixturePath());
            $response->prepare($this->request(server: ['HTTP_RANGE' => $range]));

            $this->assertSame(ResponseStatus::OK, $response->status(), $range);
            $this->assertFalse($response->headers()->has('Content-Range'), $range);
        }
    }

    public function testFileResponseAnswersConditionalRequestsWithNotModified(): void
    {
        $fileResponse = new FileResponse($this->fixturePath(), autoEtag: true);
        $etag = $fileResponse->prepare($this->request())->headers()->get('ETag');

        $conditional = (new FileResponse($this->fixturePath(), autoEtag: true))->prepare($this->request(server: ['HTTP_IF_NONE_MATCH' => (string) $etag]));

        $this->assertSame(ResponseStatus::NotModified, $conditional->status());
        $this->assertSame('', $conditional->content());
    }

    public function testHeadResponseDoesNotStreamOrDeleteTheFile(): void
    {
        $path = FileSystem::joinPaths(TESTS_TMP_PATH, 'head.txt');
        FileSystem::copyFile($this->fixturePath(), $path);
        $fileResponse = new FileResponse($path, deleteAfterSend: true);
        $fileResponse->prepare($this->request(RequestMethod::HEAD));
        ob_start();
        $fileResponse->send();
        $output = ob_get_clean();

        $this->assertSame('', $output);
        $this->assertSame('', $fileResponse->content());
        $this->assertTrue(FileSystem::exists($path));
    }

    public function testInlineFileResponseRejectsDownloadFilenameChanges(): void
    {
        $fileResponse = new FileResponse($this->fixturePath());

        $this->expectException(LogicException::class);
        $fileResponse->setFilename('download.txt');
    }

    public function testValidatorsAreNotAddedUnlessRequested(): void
    {
        $fileResponse = new FileResponse($this->fixturePath());
        $fileResponse->prepare($this->request());

        $this->assertFalse($fileResponse->headers()->has('ETag'));
        $this->assertFalse($fileResponse->headers()->has('Last-Modified'));
    }

    public function testRequestedValidatorsDependOnTheFile(): void
    {
        $first = FileSystem::joinPaths(TESTS_TMP_PATH, 'first.txt');
        $second = FileSystem::joinPaths(TESTS_TMP_PATH, 'second.txt');
        FileSystem::write($first, 'first');
        FileSystem::write($second, 'second');
        touch($first, 1_700_000_000);

        $fileResponse = (new FileResponse($first, autoEtag: true, autoLastModified: true))->prepare($this->request());
        $secondResponse = (new FileResponse($second, autoEtag: true, autoLastModified: true))->prepare($this->request());

        $this->assertSame('Tue, 14 Nov 2023 22:13:20 GMT', $fileResponse->headers()->get('Last-Modified'));
        $this->assertNotSame($fileResponse->headers()->get('ETag'), $secondResponse->headers()->get('ETag'));
    }

    /**
     * @param array<string, string> $server
     */
    #[DataProvider('sentContentProvider')]
    public function testSentBodyMatchesTheAnnouncedStatusAndHeaders(array $server, ResponseStatus $responseStatus, string $expectedBody, ?string $expectedRange): void
    {
        $path = TESTS_TMP_PATH . '/range.txt';
        FileSystem::write($path, '0123456789');
        $fileResponse = new FileResponse($path);
        $fileResponse->prepare($this->request(RequestMethod::GET, $server));

        ob_start();
        $fileResponse->send();
        $output = ob_get_clean();

        $this->assertSame($responseStatus, $fileResponse->status());
        $this->assertSame($expectedBody, $output);
        $this->assertSame((string) strlen($expectedBody), $fileResponse->headers()->get('Content-Length'));
        $this->assertSame($expectedRange, $fileResponse->headers()->get('Content-Range'));
    }

    /**
     * @return iterable<string, array{array<string, string>, ResponseStatus, string, ?string}>
     */
    public static function sentContentProvider(): iterable
    {
        yield 'whole file' => [[], ResponseStatus::OK, '0123456789', null];
        yield 'bounded range' => [['HTTP_RANGE' => 'bytes=2-5'], ResponseStatus::PartialContent, '2345', 'bytes 2-5/10'];
        yield 'single byte' => [['HTTP_RANGE' => 'bytes=0-0'], ResponseStatus::PartialContent, '0', 'bytes 0-0/10'];
        yield 'open ended range' => [['HTTP_RANGE' => 'bytes=7-'], ResponseStatus::PartialContent, '789', 'bytes 7-9/10'];
        yield 'suffix range' => [['HTTP_RANGE' => 'bytes=-3'], ResponseStatus::PartialContent, '789', 'bytes 7-9/10'];
        yield 'range clamped to the file size' => [['HTTP_RANGE' => 'bytes=8-99'], ResponseStatus::PartialContent, '89', 'bytes 8-9/10'];
        yield 'unsatisfiable range' => [['HTTP_RANGE' => 'bytes=20-30'], ResponseStatus::RangeNotSatisfiable, '', 'bytes */10'];
    }

    public function testZeroLengthFileIsStillDeletedWhenDeleteAfterSendIsEnabled(): void
    {
        $path = TESTS_TMP_PATH . '/empty.txt';
        FileSystem::write($path, '');
        $fileResponse = new FileResponse($path, deleteAfterSend: true);
        $fileResponse->prepare($this->request(RequestMethod::GET));

        $fileResponse->send();

        $this->assertFileDoesNotExist($path);
    }

    public function testAutoValidatorsRemainStableAcrossRepeatedPreparation(): void
    {
        $path = TESTS_TMP_PATH . '/validators.txt';
        FileSystem::write($path, 'body');
        $fileResponse = new FileResponse($path, autoEtag: true, autoLastModified: true);
        $request = $this->request(RequestMethod::GET);

        $fileResponse->prepare($request);
        $etag = $fileResponse->headers()->get('ETag');
        $modified = $fileResponse->headers()->get('Last-Modified');
        $fileResponse->prepare($request);

        $this->assertSame($etag, $fileResponse->headers()->get('ETag'));
        $this->assertSame($modified, $fileResponse->headers()->get('Last-Modified'));
    }

    private function fixturePath(): string
    {
        return __DIR__ . '/Fixtures/files/sample.txt';
    }

    /**
     * @param array<string, string> $server
     */
    private function request(RequestMethod $requestMethod = RequestMethod::GET, array $server = []): Request
    {
        return new Request([], [], [], [], $server + [
            'REQUEST_METHOD' => $requestMethod->value,
            'SERVER_NAME'    => 'example.test',
            'SERVER_PORT'    => '80',
        ]);
    }
}
