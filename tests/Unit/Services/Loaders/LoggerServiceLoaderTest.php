<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Log\Formatter\JsonFormatter;
use Formwork\Log\Formatter\TextFormatter;
use Formwork\Log\Handler\FileHandler;
use Formwork\Log\Handler\StderrHandler;
use Formwork\Log\Logger;
use Formwork\Log\LoggerManager;
use Formwork\Services\Container;
use Formwork\Services\Loaders\LoggerServiceLoader;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

#[CoversClass(LoggerServiceLoader::class)]
#[UsesTempDirectory]
final class LoggerServiceLoaderTest extends TestCase
{
    public function testLoggerWithoutHandlersIsCreated(): void
    {
        $logger = $this->load([]);

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame('default', $logger->channel());
        $logger->error('nothing handles this');
    }

    public function testFileHandlersWriteToTheConfiguredPath(): void
    {
        $logger = $this->load([['type' => 'file', 'path' => TESTS_TMP_PATH . '/app.log']]);

        $logger->error('something failed');
        unset($logger);

        $this->assertStringContainsString('something failed', FileSystem::read(TESTS_TMP_PATH . '/app.log'));
    }

    public function testJsonIsTheDefaultFormatOfFileHandlers(): void
    {
        $logger = $this->load([['type' => 'file', 'path' => TESTS_TMP_PATH . '/app.log']]);

        $logger->error('json message');
        unset($logger);

        $line = trim(FileSystem::read(TESTS_TMP_PATH . '/app.log'));
        $this->assertSame('json message', json_decode($line, true)['message'] ?? null);
    }

    public function testTextFormatterCanBeChosen(): void
    {
        $logger = $this->load([['type' => 'file', 'path' => TESTS_TMP_PATH . '/app.log', 'formatter' => 'text']]);

        $logger->error('text message');
        unset($logger);

        $line = trim(FileSystem::read(TESTS_TMP_PATH . '/app.log'));
        $this->assertNull(json_decode($line, true));
        $this->assertStringContainsString('text message', $line);
    }

    public function testHandlerLevelFiltersMessages(): void
    {
        $logger = $this->load([['type' => 'file', 'path' => TESTS_TMP_PATH . '/app.log', 'level' => 'error']]);

        $logger->info('too verbose');
        $logger->error('important');
        unset($logger);

        $content = FileSystem::read(TESTS_TMP_PATH . '/app.log');
        $this->assertStringNotContainsString('too verbose', $content);
        $this->assertStringContainsString('important', $content);
    }

    public function testSeveralHandlersAreAllUsed(): void
    {
        $logger = $this->load([
            ['type' => 'file', 'path' => TESTS_TMP_PATH . '/first.log'],
            ['type' => 'file', 'path' => TESTS_TMP_PATH . '/second.log', 'formatter' => 'text'],
        ]);

        $logger->warning('shared');
        unset($logger);

        $this->assertStringContainsString('shared', FileSystem::read(TESTS_TMP_PATH . '/first.log'));
        $this->assertStringContainsString('shared', FileSystem::read(TESTS_TMP_PATH . '/second.log'));
    }

    /**
     * @param array<string, string> $config
     * @param class-string          $handler
     * @param class-string          $formatter
     */
    #[DataProvider('handlerProvider')]
    public function testHandlersAreBuiltFromTheirType(array $config, string $handler, string $formatter): void
    {
        $logger = $this->load([$config]);

        $handlers = (new ReflectionProperty(Logger::class, 'handlers'))->getValue($logger);

        $this->assertCount(1, $handlers);
        $this->assertInstanceOf($handler, $handlers[0]);
        $this->assertInstanceOf($formatter, (new ReflectionProperty($handlers[0], 'formatter'))->getValue($handlers[0]));
    }

    /**
     * @return iterable<string, array{array<string, string>, class-string, class-string}>
     */
    public static function handlerProvider(): iterable
    {
        yield 'standard error with the JSON format' => [['type' => 'stderr', 'formatter' => 'json'], StderrHandler::class, JsonFormatter::class];
        yield 'file with the text format' => [['type' => 'file', 'path' => TESTS_TMP_PATH . '/app.log', 'formatter' => 'text'], FileHandler::class, TextFormatter::class];
    }

    public function testHandlersCanBeLimitedToChannels(): void
    {
        $loggerManager = $this->loadManager([['type' => 'file', 'path' => TESTS_TMP_PATH . '/panel.log', 'channels' => ['panel']]]);

        $loggerManager->get('panel')->error('from panel');
        $loggerManager->get('cache')->error('from cache');
        unset($loggerManager);

        $content = FileSystem::read(TESTS_TMP_PATH . '/panel.log');
        $this->assertStringContainsString('from panel', $content);
        $this->assertStringNotContainsString('from cache', $content);
    }

    public function testHandlersWithoutChannelsReceiveEveryChannel(): void
    {
        $loggerManager = $this->loadManager([['type' => 'file', 'path' => TESTS_TMP_PATH . '/app.log']]);

        $loggerManager->get('panel')->error('from panel');
        $loggerManager->get('cache')->error('from cache');
        unset($loggerManager);

        $content = FileSystem::read(TESTS_TMP_PATH . '/app.log');
        $this->assertStringContainsString('from panel', $content);
        $this->assertStringContainsString('from cache', $content);
    }

    public function testUnknownHandlerTypesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown log handler type: syslog');

        $this->load([['type' => 'syslog']]);
    }

    public function testHandlersWithoutATypeAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown log handler type: ');

        $this->load([['path' => TESTS_TMP_PATH . '/app.log']]);
    }

    public function testUnknownFormattersAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown log formatter: xml');

        $this->load([['type' => 'file', 'path' => TESTS_TMP_PATH . '/app.log', 'formatter' => 'xml']]);
    }

    /**
     * @param list<array<string, mixed>> $handlers
     */
    private function load(array $handlers): Logger
    {
        return $this->loadManager($handlers)->get(Logger::DEFAULT_CHANNEL);
    }

    /**
     * @param list<array<string, mixed>> $handlers
     */
    private function loadManager(array $handlers): LoggerManager
    {
        $container = new Container();
        $container->define(Container::class, $container);
        $config = new Config(['system' => ['logs' => ['handlers' => $handlers]]], resolved: true);

        return (new LoggerServiceLoader($container, $config))->load($container);
    }
}
