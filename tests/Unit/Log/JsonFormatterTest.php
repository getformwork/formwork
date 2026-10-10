<?php

namespace Formwork\Tests\Unit\Log;

use DateTimeImmutable;
use Formwork\Log\Formatter\FormatterInterface;
use Formwork\Log\Formatter\JsonFormatter;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

#[CoversClass(JsonFormatter::class)]
final class JsonFormatterTest extends TestCase
{
    private DateTimeImmutable $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->date = new DateTimeImmutable('2025-01-02 03:04:05.123456 UTC');
    }

    public function testFormatterImplementsItsInterface(): void
    {
        $this->assertInstanceOf(FormatterInterface::class, new JsonFormatter());
    }

    public function testEntriesAreJsonObjects(): void
    {
        $entry = $this->decode((new JsonFormatter())->format($this->date, 'info', 'message', ['a' => 1]));

        $this->assertSame(['datetime', 'level', 'message', 'context'], array_keys($entry));
        $this->assertSame('2025-01-02T03:04:05.123456+00:00', $entry['datetime']);
        $this->assertSame('info', $entry['level']);
        $this->assertSame('message', $entry['message']);
        $this->assertSame(['a' => 1], $entry['context']);
    }

    public function testLevelIsKeptAsGiven(): void
    {
        $entry = $this->decode((new JsonFormatter())->format($this->date, 'warning', 'message', []));

        $this->assertSame('warning', $entry['level']);
    }

    public function testPlaceholdersAreInterpolatedAndContextIsKept(): void
    {
        $entry = $this->decode((new JsonFormatter())->format($this->date, 'info', 'User {username}', ['username' => 'alice']));

        $this->assertSame('User alice', $entry['message']);
        $this->assertSame(['username' => 'alice'], $entry['context']);
    }

    public function testEntriesAreSingleLines(): void
    {
        $line = (new JsonFormatter())->format($this->date, 'info', "line one\nline two\r", ['text' => "a\nb"]);

        $this->assertDoesNotMatchRegularExpression('/[\r\n]/', $line);
        $this->assertSame("line one\nline two\r", $this->decode($line)['message']);
    }

    public function testEmptyContextIsAnEmptyList(): void
    {
        $line = (new JsonFormatter())->format($this->date, 'info', 'message', []);

        $this->assertSame([], $this->decode($line)['context']);
    }

    public function testUnicodeAndSlashesAreNotEscapedNeedlessly(): void
    {
        $line = (new JsonFormatter())->format($this->date, 'info', 'è /path', []);

        $this->assertSame('è /path', $this->decode($line)['message']);
    }

    public function testDatesInTheContextUseTheJsonDateFormat(): void
    {
        $entry = $this->decode((new JsonFormatter())->format($this->date, 'info', 'message', ['when' => $this->date]));

        $this->assertSame('2025-01-02T03:04:05.123456+00:00', $entry['context']['when']);
    }

    public function testExceptionsAreDescribedInTheContext(): void
    {
        $entry = $this->decode((new JsonFormatter())->format($this->date, 'error', 'message', [
            'exception' => new RuntimeException('failure', 3, new \LogicException('cause')),
        ]));

        $exception = $entry['context']['exception'];
        $this->assertSame(RuntimeException::class, $exception['class']);
        $this->assertSame('failure', $exception['message']);
        $this->assertSame(3, $exception['code']);
        $this->assertSame('cause', $exception['previous']['message']);
    }

    public function testInvalidUtf8DoesNotMakeLoggingFail(): void
    {
        $line = (new JsonFormatter())->format($this->date, 'info', "invalid \xff byte", ['value' => "\xff"]);

        $this->assertStringContainsString('invalid', $line);
        $this->assertIsArray(json_decode($line, true));
    }

    public function testNonFiniteNumbersDoNotMakeLoggingFail(): void
    {
        $line = (new JsonFormatter())->format($this->date, 'info', 'message', ['value' => INF, 'other' => NAN]);

        $this->assertIsArray(json_decode($line, true));
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $line): array
    {
        $decoded = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        return $decoded;
    }
}
