<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\ResponseStatus;
use Formwork\Http\Utils\Header;
use Formwork\Tests\PhpServer;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Header::class)]
final class ResponseHeaderUtilsTest extends TestCase
{
    private static PhpServer $server;

    public static function setUpBeforeClass(): void
    {
        self::$server = PhpServer::start(__DIR__ . '/Fixtures/endpoint.php');
    }

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }

    public function testNotFoundSendsTheNotFoundStatus(): void
    {
        $this->assertSame(404, self::$server->request('action=not-found')['status']);
    }

    public function testRedirectRejectsNonRedirectionStatuses(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('only 3XX statuses are allowed');
        Header::redirect('/next', ResponseStatus::OK);
    }

    public function testRedirectSendsTheStatusAndTheLocation(): void
    {
        $response = self::$server->request('action=redirect');

        $this->assertSame(303, $response['status']);
        $this->assertContains('Location: /target', $response['headers']);
        $this->assertSame('', $response['body']);
    }

    /**
     * @param list<int|string> $data
     */
    #[DataProvider('makeProvider')]
    public function testMakeJoinsValuesAndParameters(array $data, string $expected): void
    {
        $this->assertSame($expected, Header::make($data));
    }

    /**
     * @return iterable<string, array{array<int|string, string>, string}>
     */
    public static function makeProvider(): iterable
    {
        yield 'no data' => [[], ''];
        yield 'single value' => [['text/html'], 'text/html'];
        yield 'value with a parameter' => [['application/json', 'charset' => 'utf-8'], 'application/json; charset=utf-8'];
        yield 'several parameters' => [['attachment', 'filename' => 'sample.txt', 'size' => '12'], 'attachment; filename=sample.txt; size=12'];
        yield 'parameters before values' => [['a' => 'b', 'c'], 'a=b; c'];
    }

    public function testSendAddsTheTrimmedHeader(): void
    {
        $this->assertContains('X-Formwork-Test: value', self::$server->request('action=send')['headers']);
    }

    public function testSendReplacesTheSameHeaderUnlessTold(): void
    {
        $this->assertSame(
            ['X-Formwork-Test: second', 'X-Formwork-Test: third'],
            $this->headersNamed('X-Formwork-Test', self::$server->request('action=replace')['headers']),
        );
    }

    public function testSendStatusSetsTheResponseStatus(): void
    {
        $this->assertSame(201, self::$server->request('action=status')['status']);
    }

    public function testSendFailsOnceTheResponseOutputHasStarted(): void
    {
        $response = self::$server->request('action=after-output');

        $this->assertSame('output|Cannot send X-Formwork-Test header, HTTP headers already sent', $response['body']);
        $this->assertSame([], $this->headersNamed('X-Formwork-Test', $response['headers']));
    }

    public function testContentTypeSendsTheContentTypeHeader(): void
    {
        $this->assertContains('Content-Type: text/plain; charset=utf-8', self::$server->request('action=content-type')['headers']);
    }

    /**
     * @param list<string> $headers
     *
     * @return list<string>
     */
    private function headersNamed(string $name, array $headers): array
    {
        return array_values(array_filter($headers, static fn(string $header): bool => str_starts_with($header, $name . ':')));
    }
}
