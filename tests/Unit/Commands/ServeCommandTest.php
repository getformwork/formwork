<?php

namespace Formwork\Tests\Unit\Commands;

use Formwork\Commands\ServeCommand;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use League\CLImate\Util\Writer\Buffer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use ReflectionProperty;
use UnexpectedValueException;

#[CoversClass(ServeCommand::class)]
#[UsesApplication]
final class ServeCommandTest extends TestCase
{
    use ProvidesCommandAssertions;

    private ServeCommand $command;

    private Buffer $buffer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buffer = new Buffer();

        $this->command = new ServeCommand($this->bufferedConsole($this->buffer));
    }

    /**
     * @param list<string> $arguments
     * @param list<string> $expectations
     */
    #[DataProvider('commandResultProvider')]
    public function testCommandExitsWithTheExpectedResult(array $arguments, int $exitCode, array $expectations): void
    {
        $this->assertCommand(ServeCommand::class, $arguments, $exitCode, $expectations);
    }

    /**
     * @return iterable<string, array{list<string>, int, list<string>}>
     */
    public static function commandResultProvider(): iterable
    {
        yield 'help' => [['--help'], 0, ['--host', '--port', '--retry']];

        foreach (['with a space' => 'not a host', 'with a scheme' => 'http://localhost', 'with a port' => 'localhost:8000', 'with a path' => 'localhost/path'] as $name => $host) {
            yield "invalid host $name" => [['--host=' . $host], 1, ['failed to listen on invalid host']];
        }
    }

    /**
     * @param list<mixed> $arguments
     */
    #[DataProvider('helperProvider')]
    public function testHelpersReturnTheExpectedValues(string $method, array $arguments, mixed $expected): void
    {
        $this->assertSame($expected, (new ReflectionMethod($this->command, $method))->invoke($this->command, ...$arguments));
    }

    /**
     * @return iterable<string, array{string, list<mixed>, mixed}>
     */
    public static function helperProvider(): iterable
    {
        foreach (['localhost' => true, '127.0.0.1' => true, '0.0.0.0' => true, '::1' => true, '::' => true, 'example.test' => true, '' => false, 'local host' => false, 'http://localhost' => false] as $host => $valid) {
            yield "validity of the host '$host'" => ['isValidHost', [$host], $valid];
        }

        foreach (['localhost' => 'localhost', '192.168.0.10' => '192.168.0.10', '::1' => '[::1]', '::' => '[::]', '2001:db8::1' => '[2001:db8::1]'] as $host => $formatted) {
            yield "binding of the host '$host'" => ['formatHost', [$host], $formatted];
        }

        foreach ([[0.0005, '500 μs'], [0.25, '250 ms'], [1.5, '1.5 s'], [42.0, '42 s'], [60.0, '60 s'], [125.0, '2 m 5 s']] as [$seconds, $formatted]) {
            yield "time of $seconds seconds" => ['formatTime', [$seconds], $formatted];
        }

        foreach ([200 => 'blue', 299 => 'blue', 302 => 'green', 404 => 'yellow'] as $status => $color) {
            yield "color of the status $status" => ['colorStatus', [$status], "<$color>$status</$color>"];
        }

        yield 'message with an IPv4 address' => ['splitMessage', ['127.0.0.1:50000 [200]: GET /index.php'], ['127.0.0.1', '50000', '[200]: GET /index.php']];
        yield 'message with an IPv6 address' => ['splitMessage', ['[::1]:50000 Accepted'], ['[::1]', '50000', 'Accepted']];
        yield 'unknown message' => ['splitMessage', ['something unexpected'], []];
    }

    /**
     * @param list<mixed> $arguments
     */
    #[DataProvider('unexpectedValueProvider')]
    public function testUnexpectedValuesAreRejected(string $method, array $arguments): void
    {
        $this->expectException(UnexpectedValueException::class);

        (new ReflectionMethod($this->command, $method))->invoke($this->command, ...$arguments);
    }

    /**
     * @return iterable<string, array{string, list<mixed>}>
     */
    public static function unexpectedValueProvider(): iterable
    {
        yield 'status code' => ['colorStatus', [600]];
        yield 'type of raw line' => ['outputRawLine', ['other', 'line']];
    }

    /**
     * @param list<string> $lines
     */
    #[DataProvider('serverOutputProvider')]
    public function testServerOutput(string $stream, array $lines, string $expected): void
    {
        (new ReflectionProperty($this->command, 'startTime'))->setValue($this->command, microtime(true));
        (new ReflectionMethod($this->command, 'handleOutput'))->invoke($this->command, $stream, $lines);

        $this->assertStringContainsString($expected, $this->buffer->get());
        $this->assertSame($expected === '', $this->buffer->get() === '');
    }

    /**
     * @return iterable<string, array{string, list<string>, string}>
     */
    public static function serverOutputProvider(): iterable
    {
        $time = '[Thu Jan  1 00:00:00 2026] ';

        yield 'empty lines are skipped' => ['out', ['', ''], ''];
        yield 'lines without a timestamp are printed raw' => ['out', ['plain output'], 'plain output'];
        yield 'closed requests are logged' => ['err', ["{$time}127.0.0.1:50000 Accepted", "{$time}127.0.0.1:50000 [200]: GET /page", "{$time}127.0.0.1:50000 Closing"], 'GET /page'];
        yield 'startup message shows the address' => ['err', ["{$time}PHP 8.3.0 Development Server (http://[::1]:8123) started"], 'PHP runtime 8.3.0'];
    }

    public function testServerErrorStatusesAreColorized(): void
    {
        $colorStatus = new ReflectionMethod($this->command, 'colorStatus');

        $this->assertSame('<red>500</red>', $colorStatus->invoke($this->command, 500));
        $this->assertSame('<red>599</red>', $colorStatus->invoke($this->command, 599));
    }

    public function testClosedConnectionsWithoutARequestAreIgnored(): void
    {
        $time = '[Thu Jan  1 00:00:00 2026] ';

        (new ReflectionMethod($this->command, 'handleOutput'))->invoke($this->command, 'err', ["{$time}127.0.0.1:50001 Accepted", "{$time}127.0.0.1:50001 Closing"]);

        $this->assertSame('', $this->buffer->get());
    }

    public function testMinutesAreFormattedWithoutDeprecations(): void
    {
        $messages = [];
        set_error_handler(static function (int $severity, string $message) use (&$messages): bool {
            $messages[] = $message;

            return true;
        });

        try {
            $formatted = (new ReflectionMethod($this->command, 'formatTime'))->invoke($this->command, 125.7);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $messages);
        $this->assertSame('2 m 6 s', $formatted);
    }
}
