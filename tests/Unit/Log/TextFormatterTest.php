<?php

namespace Formwork\Tests\Unit\Log;

use DateTimeImmutable;
use Formwork\Log\Formatter\FormatterInterface;
use Formwork\Log\Formatter\TextFormatter;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

#[CoversClass(TextFormatter::class)]
final class TextFormatterTest extends TestCase
{
    private DateTimeImmutable $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->date = new DateTimeImmutable('2025-01-02 03:04:05.123456 UTC');
    }

    public function testFormatterImplementsItsInterface(): void
    {
        $this->assertInstanceOf(FormatterInterface::class, new TextFormatter());
    }

    public function testLinesContainTheTimestampLevelAndMessage(): void
    {
        $line = (new TextFormatter())->format($this->date, 'info', 'Something happened', []);

        $this->assertSame('[2025-01-02 03:04:05.123456] INFO: Something happened', $line);
    }

    #[DataProvider('levelProvider')]
    public function testLevelsAreUppercased(string $level): void
    {
        $line = (new TextFormatter())->format($this->date, $level, 'message', []);

        $this->assertStringContainsString(': message', $line);
        $this->assertStringContainsString('] ' . strtoupper($level) . ':', $line);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function levelProvider(): iterable
    {
        foreach (['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'] as $level) {
            yield $level => [$level];
        }
    }

    public function testPlaceholdersAreInterpolated(): void
    {
        $line = (new TextFormatter(false))->format($this->date, 'info', 'User {username} logged in', ['username' => 'alice']);

        $this->assertSame('[2025-01-02 03:04:05.123456] INFO: User alice logged in', $line);
    }

    public function testContextIsAppendedAsJson(): void
    {
        $line = (new TextFormatter())->format($this->date, 'info', 'message', ['a' => 1, 'b' => ['c' => true]]);

        $this->assertSame('[2025-01-02 03:04:05.123456] INFO: message {"a":1,"b":{"c":true}}', $line);
    }

    public function testContextCanBeOmitted(): void
    {
        $line = (new TextFormatter(false))->format($this->date, 'info', 'message', ['a' => 1]);

        $this->assertSame('[2025-01-02 03:04:05.123456] INFO: message', $line);
    }

    public function testEmptyContextIsNotAppended(): void
    {
        $line = (new TextFormatter())->format($this->date, 'info', 'message', []);

        $this->assertSame('[2025-01-02 03:04:05.123456] INFO: message', $line);
    }

    public function testDatesInTheContextAreFormattedLikeTheTimestamp(): void
    {
        $line = (new TextFormatter())->format($this->date, 'info', 'message', ['when' => $this->date]);

        $this->assertStringContainsString('"when":"2025-01-02 03:04:05.123456"', $line);
    }

    public function testExceptionsInTheContextAreDescribed(): void
    {
        $line = (new TextFormatter())->format($this->date, 'error', 'message', ['exception' => new RuntimeException('failure', 3)]);

        $this->assertStringContainsString('"class":"RuntimeException"', $line);
        $this->assertStringContainsString('"message":"failure"', $line);
    }

    public function testTimestampUsesTheTimeZoneOfTheGivenDate(): void
    {
        $date = new DateTimeImmutable('2025-01-02 03:04:05.000000', new \DateTimeZone('Europe/Rome'));

        $line = (new TextFormatter())->format($date, 'info', 'message', []);

        $this->assertStringStartsWith('[2025-01-02 03:04:05.000000]', $line);
    }

    #[DataProvider('multilineProvider')]
    public function testEntriesAreAlwaysASingleLine(string $message, array $context): void
    {
        $line = (new TextFormatter())->format($this->date, 'info', $message, $context);

        $this->assertDoesNotMatchRegularExpression('/[\r\n]/', $line, 'Line breaks in logged data must not be able to forge log entries');
    }

    /**
     * @return iterable<string, array{string, array<mixed>}>
     */
    public static function multilineProvider(): iterable
    {
        $forged = "\n[2025-01-02 03:04:05.000000] CRITICAL: forged entry";

        yield 'line feed in the message' => ["message{$forged}", []];
        yield 'carriage return in the message' => ["message\r[2025-01-02 03:04:05.000000] CRITICAL: forged entry", []];
        yield 'line breaks in an interpolated value' => ['User {name}', ['name' => "bob{$forged}"]];
        yield 'line breaks in the context' => ['message', ['name' => "bob{$forged}"]];
    }

    public function testInvalidUtf8DoesNotMakeLoggingFail(): void
    {
        $line = (new TextFormatter())->format($this->date, 'info', "invalid \xff byte", ['value' => "\xff"]);

        $this->assertStringContainsString('invalid', $line);
    }

    public function testNonFiniteNumbersDoNotMakeLoggingFail(): void
    {
        $line = (new TextFormatter())->format($this->date, 'info', 'message', ['value' => INF, 'other' => NAN]);

        $this->assertStringContainsString('message', $line);
    }
}
