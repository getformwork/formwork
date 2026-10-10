<?php

namespace Formwork\Tests\Unit\Log;

use DateTimeImmutable;
use Formwork\Log\Formatter\JsonFormatter;
use Formwork\Log\Handler\AbstractHandler;
use Formwork\Log\Handler\HandlerInterface;
use Formwork\Log\Logger;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Log\Fixtures\ExposedHandler;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LogLevel;

#[CoversClass(AbstractHandler::class)]
final class AbstractHandlerTest extends TestCase
{
    public function testHandlersImplementTheirInterface(): void
    {
        $this->assertInstanceOf(HandlerInterface::class, new ExposedHandler(new JsonFormatter()));
    }

    public function testDebugIsTheDefaultLevelAndAcceptsEverything(): void
    {
        $handler = new ExposedHandler(new JsonFormatter());

        foreach (array_keys(Logger::LOG_LEVELS) as $level) {
            $this->assertTrue($handler->accepts($level), $level);
        }
    }

    /**
     * @param list<string> $accepted
     */
    #[DataProvider('thresholdProvider')]
    public function testOnlyRecordsAtLeastAsSevereAsTheThresholdAreAccepted(string $threshold, array $accepted): void
    {
        $handler = new ExposedHandler(new JsonFormatter(), $threshold);

        $actual = array_values(array_filter(
            array_keys(Logger::LOG_LEVELS),
            static fn(string $level): bool => $handler->accepts($level),
        ));

        $this->assertSame($accepted, $actual);
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function thresholdProvider(): iterable
    {
        yield 'emergency' => [LogLevel::EMERGENCY, ['emergency']];
        yield 'alert' => [LogLevel::ALERT, ['emergency', 'alert']];
        yield 'critical' => [LogLevel::CRITICAL, ['emergency', 'alert', 'critical']];
        yield 'error' => [LogLevel::ERROR, ['emergency', 'alert', 'critical', 'error']];
        yield 'warning' => [LogLevel::WARNING, ['emergency', 'alert', 'critical', 'error', 'warning']];
        yield 'notice' => [LogLevel::NOTICE, ['emergency', 'alert', 'critical', 'error', 'warning', 'notice']];
        yield 'info' => [LogLevel::INFO, ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info']];
        yield 'debug' => [LogLevel::DEBUG, ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug']];
    }

    public function testInvalidThresholdsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log level: "verbose"');
        new ExposedHandler(new JsonFormatter(), 'verbose');
    }

    public function testInvalidRecordLevelsAreRejected(): void
    {
        $handler = new ExposedHandler(new JsonFormatter());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log level: "verbose"');
        $handler->accepts('verbose');
    }

    public function testHandleUsesTheThreshold(): void
    {
        $handler = new ExposedHandler(new JsonFormatter(), LogLevel::ERROR);

        $handler->handle(new DateTimeImmutable(), 'debug', 'ignored', []);
        $handler->handle(new DateTimeImmutable(), 'error', 'kept', []);
        $handler->handle(new DateTimeImmutable(), 'alert', 'kept', []);

        $this->assertSame(['error', 'alert'], $handler->handled);
    }
}
