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
        $exposedHandler = new ExposedHandler(new JsonFormatter());

        foreach (array_keys(Logger::LOG_LEVELS) as $level) {
            $this->assertTrue($exposedHandler->accepts($level), $level);
        }
    }

    /**
     * @param list<string> $accepted
     */
    #[DataProvider('thresholdProvider')]
    public function testOnlyRecordsAtLeastAsSevereAsTheThresholdAreAccepted(string $threshold, array $accepted): void
    {
        $exposedHandler = new ExposedHandler(new JsonFormatter(), $this->unchecked($threshold));

        $actual = array_values(array_filter(
            array_keys(Logger::LOG_LEVELS),
            $exposedHandler->accepts(...),
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
        $invalidArgumentException = $this->captureException(InvalidArgumentException::class, fn() => new ExposedHandler(new JsonFormatter(), $this->unchecked('verbose')));

        $this->assertSame('Invalid log level: "verbose"', $invalidArgumentException->getMessage());
    }

    public function testInvalidRecordLevelsAreRejected(): void
    {
        $exposedHandler = new ExposedHandler(new JsonFormatter());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log level: "verbose"');
        $exposedHandler->accepts('verbose');
    }

    public function testHandleUsesTheThreshold(): void
    {
        $exposedHandler = new ExposedHandler(new JsonFormatter(), LogLevel::ERROR);

        $exposedHandler->handle(new DateTimeImmutable(), 'debug', 'ignored', []);
        $exposedHandler->handle(new DateTimeImmutable(), 'error', 'kept', []);
        $exposedHandler->handle(new DateTimeImmutable(), 'alert', 'kept', []);

        $this->assertSame(['error', 'alert'], $exposedHandler->handled);
    }
}
