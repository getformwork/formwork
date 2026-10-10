<?php

namespace Formwork\Tests\Unit\Log;

use DateTimeImmutable;
use Formwork\Log\Logger;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Log\Fixtures\RecordingHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

#[CoversClass(Logger::class)]
final class LoggerTest extends TestCase
{
    public function testLoggerIsAPsrLogger(): void
    {
        $logger = new Logger();

        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertInstanceOf(AbstractLogger::class, $logger);
    }

    public function testLoggingWithoutHandlersDoesNothing(): void
    {
        (new Logger())->info('message');

        $this->addToAssertionCount(1);
    }

    public function testRecordsAreForwardedToTheHandlers(): void
    {
        $logger = new Logger();
        $handler = new RecordingHandler();
        $logger->addHandler($handler);

        $logger->log(LogLevel::WARNING, 'Something happened', ['key' => 'value']);

        $this->assertCount(1, $handler->records);
        [$datetime, $level, $message, $context] = $handler->records[0];
        $this->assertSame('warning', $level);
        $this->assertSame('Something happened', $message);
        $this->assertSame(['key' => 'value'], $context);
        $this->assertInstanceOf(DateTimeImmutable::class, $datetime);
    }

    public function testEveryHandlerReceivesTheRecordInOrder(): void
    {
        $logger = new Logger();
        $first = new RecordingHandler();
        $second = new RecordingHandler();
        $logger->addHandler($first);
        $logger->addHandler($second);

        $logger->error('message');

        $this->assertCount(1, $first->records);
        $this->assertCount(1, $second->records);
        $this->assertSame($first->records[0][0], $second->records[0][0], 'Handlers must receive the same timestamp');
    }

    #[DataProvider('levelMethodProvider')]
    public function testConvenienceMethodsUseTheMatchingLevel(string $method, string $level): void
    {
        $logger = new Logger();
        $handler = new RecordingHandler();
        $logger->addHandler($handler);

        $logger->{$method}('message', ['a' => 1]);

        $this->assertSame($level, $handler->records[0][1]);
        $this->assertSame('message', $handler->records[0][2]);
        $this->assertSame(['a' => 1], $handler->records[0][3]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function levelMethodProvider(): iterable
    {
        foreach (['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'] as $level) {
            yield $level => [$level, $level];
        }
    }

    public function testLevelsAreOrderedFromTheMostToTheLeastSevere(): void
    {
        $this->assertSame(
            ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'],
            array_keys(Logger::LOG_LEVELS),
        );
        $this->assertSame(range(0, 7), array_values(Logger::LOG_LEVELS));
    }

    public function testStringableMessagesAreConvertedToStrings(): void
    {
        $logger = new Logger();
        $handler = new RecordingHandler();
        $logger->addHandler($handler);

        $logger->info(new class implements Stringable {
            public function __toString(): string
            {
                return 'from object';
            }
        });

        $this->assertSame('from object', $handler->records[0][2]);
    }

    public function testTimestampsHaveMicrosecondPrecisionAndAreCurrent(): void
    {
        $logger = new Logger();
        $handler = new RecordingHandler();
        $logger->addHandler($handler);
        $before = microtime(true);

        $logger->info('message');

        $timestamp = (float) $handler->records[0][0]->format('U.u');
        $this->assertGreaterThanOrEqual(floor($before), $timestamp);
        $this->assertLessThanOrEqual(microtime(true) + 1, $timestamp);
    }

    #[DataProvider('invalidLevelProvider')]
    public function testInvalidLevelsAreRejected(mixed $level): void
    {
        $logger = new Logger();
        $handler = new RecordingHandler();
        $logger->addHandler($handler);

        try {
            $logger->log($level, 'message');
            $this->fail('The level should have been rejected.');
        } catch (InvalidArgumentException) {
        }

        $this->assertSame([], $handler->records);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidLevelProvider(): iterable
    {
        yield 'unknown level' => ['verbose'];
        yield 'uppercase level' => ['INFO'];
        yield 'numeric level' => [3];
        yield 'null' => [null];
        yield 'empty string' => [''];
        yield 'array' => [['info']];
    }

    public function testContextIsPassedUntouched(): void
    {
        $logger = new Logger();
        $handler = new RecordingHandler();
        $logger->addHandler($handler);
        $exception = new \RuntimeException('failure');

        $logger->error('message', ['exception' => $exception, 'nested' => ['a' => [1, 2]]]);

        $this->assertSame($exception, $handler->records[0][3]['exception']);
        $this->assertSame(['a' => [1, 2]], $handler->records[0][3]['nested']);
    }
}
