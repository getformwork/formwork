<?php

namespace Formwork\Tests\Unit\Log;

use Formwork\Log\LoggerManager;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Log\Fixtures\RecordingHandlerFixture;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(LoggerManager::class)]
final class LoggerManagerTest extends TestCase
{
    public function testLoggersAreNamedAfterTheirChannel(): void
    {
        $this->assertSame('cache', (new LoggerManager())->get('cache')->channel());
    }

    public function testTheSameLoggerIsReturnedForAChannel(): void
    {
        $loggerManager = new LoggerManager();

        $this->assertSame($loggerManager->get('panel'), $loggerManager->get('panel'));
        $this->assertNotSame($loggerManager->get('panel'), $loggerManager->get('cache'));
    }

    public function testLoggersShareTheHandlers(): void
    {
        $recordingHandler = new RecordingHandlerFixture();
        $loggerManager = new LoggerManager([$recordingHandler]);

        $loggerManager->get('panel')->info('first');
        $loggerManager->get('cache')->info('second');

        $this->assertSame(['panel', 'cache'], array_column($recordingHandler->records, 4));
    }

    public function testHandlersAddedLaterReachExistingAndNewLoggers(): void
    {
        $loggerManager = new LoggerManager();
        $logger = $loggerManager->get('panel');
        $recordingHandler = new RecordingHandlerFixture();

        $loggerManager->addHandler($recordingHandler);
        $logger->info('first');
        $loggerManager->get('cache')->info('second');

        $this->assertSame(['panel', 'cache'], array_column($recordingHandler->records, 4));
    }
}
