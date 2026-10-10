<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Log\Logger;
use Formwork\Services\Container;
use Formwork\Services\Loaders\LoggerServiceLoader;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(LoggerServiceLoader::class)]
final class LoggerServiceLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testLoggerWithoutHandlersIsCreated(): void
    {
        $logger = $this->load([]);

        $this->assertInstanceOf(Logger::class, $logger);
        $logger->error('nothing handles this');
        $this->addToAssertionCount(1);
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
        $this->assertIsArray(json_decode($line, true));
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

    public function testUnknownHandlerTypesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown log handler type: syslog');

        $this->load([['type' => 'syslog']]);
    }

    public function testHandlersWithoutATypeAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->load([['path' => TESTS_TMP_PATH . '/app.log']]);
    }

    public function testUnknownFormattersAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown log formatter: xml');

        $this->load([['type' => 'file', 'path' => TESTS_TMP_PATH . '/app.log', 'formatter' => 'xml']]);
    }

    /**
     * @param list<array<string, string>> $handlers
     */
    private function load(array $handlers): Logger
    {
        $container = new Container();
        $container->define(Container::class, $container);
        $config = new Config(['system' => ['logs' => ['handlers' => $handlers]]], resolved: true);

        return (new LoggerServiceLoader($container, $config))->load($container);
    }
}
