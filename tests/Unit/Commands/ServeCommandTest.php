<?php

namespace Formwork\Tests\Unit\Commands;

use Formwork\Cms\App;
use Formwork\Commands\CommandInterface;
use Formwork\Commands\ServeCommand;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Commands\Fixtures\RunsCommands;
use League\CLImate\CLImate;
use League\CLImate\Util\Writer\Buffer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use ReflectionProperty;
use UnexpectedValueException;

#[CoversClass(ServeCommand::class)]
final class ServeCommandTest extends TestCase
{
    use RunsCommands;

    private ServeCommand $command;

    private Buffer $buffer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buffer = new Buffer();

        $climate = new CLImate();
        $climate->output->add('out', $this->buffer)->defaultTo('out');
        $climate->output->add('error', $this->buffer);
        $climate->forceAnsiOff();

        $this->command = new ServeCommand($climate);
    }

    public function testCommandImplementsTheInterface(): void
    {
        $this->assertInstanceOf(CommandInterface::class, $this->command);
    }

    public function testHelpShowsTheOptions(): void
    {
        $result = $this->runCommand(ServeCommand::class, ['--help']);

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);

        foreach (['--host', '--port', '--retry'] as $option) {
            $this->assertStringContainsString($option, $result['output']);
        }
    }

    #[DataProvider('invalidHostProvider')]
    public function testInvalidHostsAreRejected(string $host): void
    {
        $result = $this->runCommand(ServeCommand::class, ['--host=' . $host]);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('failed to listen on invalid host', $result['error'] . $result['output']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidHostProvider(): iterable
    {
        yield 'with a space' => ['not a host'];
        yield 'with a scheme' => ['http://localhost'];
        yield 'with a port' => ['localhost:8000'];
        yield 'with a path' => ['localhost/path'];
    }

    #[DataProvider('hostProvider')]
    public function testHostValidation(string $host, bool $expected): void
    {
        $this->assertSame($expected, $this->invoke('isValidHost', $host));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function hostProvider(): iterable
    {
        yield 'localhost' => ['localhost', true];
        yield 'IPv4 loopback' => ['127.0.0.1', true];
        yield 'IPv4 wildcard' => ['0.0.0.0', true];
        yield 'IPv6 loopback' => ['::1', true];
        yield 'IPv6 wildcard' => ['::', true];
        yield 'domain' => ['example.test', true];
        yield 'empty' => ['', false];
        yield 'with a space' => ['local host', false];
        yield 'with a scheme' => ['http://localhost', false];
    }

    #[DataProvider('formattedHostProvider')]
    public function testHostsAreFormattedForBinding(string $host, string $expected): void
    {
        $this->assertSame($expected, $this->invoke('formatHost', $host));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function formattedHostProvider(): iterable
    {
        yield 'hostname' => ['localhost', 'localhost'];
        yield 'IPv4' => ['192.168.0.10', '192.168.0.10'];
        yield 'IPv6 loopback' => ['::1', '[::1]'];
        yield 'IPv6 wildcard' => ['::', '[::]'];
        yield 'IPv6 address' => ['2001:db8::1', '[2001:db8::1]'];
    }

    #[DataProvider('timeProvider')]
    public function testTimeIntervalsAreFormatted(float $seconds, string $expected): void
    {
        $this->assertSame($expected, $this->invoke('formatTime', $seconds));
    }

    /**
     * @return iterable<string, array{float, string}>
     */
    public static function timeProvider(): iterable
    {
        yield 'microseconds' => [0.0005, '500 μs'];
        yield 'milliseconds' => [0.25, '250 ms'];
        yield 'a second and a half' => [1.5, '1.5 s'];
        yield 'seconds' => [42.0, '42 s'];
        yield 'exactly one minute' => [60.0, '60 s'];
        yield 'minutes' => [125.0, '2 m 5 s'];
    }

    public function testMinutesAreFormattedWithoutDeprecations(): void
    {
        $messages = [];

        set_error_handler(static function (int $severity, string $message) use (&$messages): bool {
            $messages[] = $message;
            return true;
        });

        try {
            $formatted = $this->invoke('formatTime', 125.7);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $messages);
        $this->assertSame('2 m 6 s', $formatted);
    }

    #[DataProvider('statusProvider')]
    public function testStatusCodesAreColorized(int $status, string $expected): void
    {
        $this->assertSame($expected, $this->invoke('colorStatus', $status));
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function statusProvider(): iterable
    {
        yield 'success' => [200, '<blue>200</blue>'];
        yield 'last success' => [299, '<blue>299</blue>'];
        yield 'redirection' => [302, '<green>302</green>'];
        yield 'client error' => [404, '<yellow>404</yellow>'];
        yield 'server error' => [500, '<red>500</red>'];
        yield 'last server error' => [599, '<red>599</red>'];
    }

    public function testUnexpectedStatusCodesAreRejected(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->invoke('colorStatus', 600);
    }

    public function testMessagesAreSplitInRemoteAddressPortAndInformation(): void
    {
        $this->assertSame(
            ['127.0.0.1', '50000', '[200]: GET /index.php'],
            $this->invoke('splitMessage', '127.0.0.1:50000 [200]: GET /index.php'),
        );
    }

    public function testMessagesWithIpv6AddressesAreSplit(): void
    {
        $this->assertSame(
            ['[::1]', '50000', 'Accepted'],
            $this->invoke('splitMessage', '[::1]:50000 Accepted'),
        );
    }

    public function testUnknownMessagesAreNotSplit(): void
    {
        $this->assertSame([], $this->invoke('splitMessage', 'something unexpected'));
    }

    public function testRawStandardOutputLinesArePrintedAsTheyAre(): void
    {
        $this->invoke('outputRawLine', 'out', 'raw line');

        $this->assertStringContainsString('raw line', $this->buffer->get());
    }

    public function testRawErrorLinesArePrinted(): void
    {
        $this->invoke('outputRawLine', 'err', 'error line');

        $this->assertStringContainsString('error line', $this->buffer->get());
    }

    public function testRawLinesOfUnknownTypeAreRejected(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->invoke('outputRawLine', 'other', 'line');
    }

    public function testRequestsAreLoggedWhenTheyAreClosed(): void
    {
        $this->invoke('handleOutput', 'err', [
            '[Thu Jan  1 00:00:00 2026] 127.0.0.1:50000 Accepted',
            '[Thu Jan  1 00:00:00 2026] 127.0.0.1:50000 [200]: GET /page',
            '[Thu Jan  1 00:00:00 2026] 127.0.0.1:50000 Closing',
        ]);

        $this->assertStringContainsString('GET', $this->buffer->get());
        $this->assertStringContainsString('/page', $this->buffer->get());
    }

    public function testClosedConnectionsWithoutARequestAreIgnored(): void
    {
        $this->invoke('handleOutput', 'err', [
            '[Thu Jan  1 00:00:00 2026] 127.0.0.1:50001 Accepted',
            '[Thu Jan  1 00:00:00 2026] 127.0.0.1:50001 Closing',
        ]);

        $this->assertSame('', $this->buffer->get());
    }

    public function testStartupMessageShowsTheListeningAddress(): void
    {
        $this->setProperty('host', '::1');
        $this->setProperty('port', 8123);
        $this->setProperty('startTime', microtime(true));

        $this->invoke('handleOutput', 'err', ['[Thu Jan  1 00:00:00 2026] PHP 8.3.0 Development Server (http://[::1]:8123) started']);

        $output = $this->buffer->get();

        $this->assertStringContainsString(App::VERSION, $output);
        $this->assertStringContainsString('http://[::1]:8123/', $output);
        $this->assertStringContainsString('PHP runtime 8.3.0', $output);
    }

    public function testEmptyLinesAreSkipped(): void
    {
        $this->invoke('handleOutput', 'out', ['', '']);

        $this->assertSame('', $this->buffer->get());
    }

    public function testLinesWithoutATimestampArePrintedRaw(): void
    {
        $this->invoke('handleOutput', 'out', ['plain output']);

        $this->assertStringContainsString('plain output', $this->buffer->get());
    }

    private function invoke(string $method, mixed ...$arguments): mixed
    {
        return (new ReflectionMethod($this->command, $method))->invoke($this->command, ...$arguments);
    }

    private function setProperty(string $name, mixed $value): void
    {
        (new ReflectionProperty($this->command, $name))->setValue($this->command, $value);
    }
}
