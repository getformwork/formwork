<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Backup\Backupper;
use Formwork\Cms\App;
use Formwork\Http\Request;
use Formwork\Panel\Controllers\ToolsController;
use Formwork\Parsers\Json;
use Formwork\Router\Router;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\BuildsPanelControllers;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

#[CoversClass(ToolsController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class ToolsControllerTest extends TestCase
{
    use BuildsPanelControllers;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $logHandlers = [];

    protected function setUp(): void
    {
        parent::setUp();

        FileSystem::createDirectory(FileSystem::joinPaths(TESTS_TMP_PATH, 'logs'));

        $root = App::instance()->rootPath();

        FileSystem::write($root . '/composer.json', Json::encode([
            'require' => ['php' => '>=8.3', 'ext-mbstring' => '*', 'ext-zip' => '*', 'league/commonmark' => '^2.4'],
        ]));
        FileSystem::write($root . '/composer.lock', Json::encode([
            'packages' => [['name' => 'league/commonmark', 'version' => '2.4.0']],
        ]));
    }

    protected function tearDown(): void
    {
        $root = App::instance()->rootPath();

        FileSystem::delete($root . '/composer.json');
        FileSystem::delete($root . '/composer.lock');

        $this->closePanelSession();
        parent::tearDown();
    }

    public function testIndexRedirectsToTheBackups(): void
    {
        $response = $this->controller()->index();

        $this->assertOutcome(['to' => 'panel.tools.backups'], $response);
    }

    public function testRequiredDependenciesExcludePhpAndExtensions(): void
    {
        $dependencies = $this->invoke('getRequiredDependencies');

        $this->assertContains('league/commonmark', $dependencies);
        $this->assertNotContains('php', $dependencies);

        foreach ($dependencies as $dependency) {
            $this->assertStringStartsNotWith('ext-', $dependency);
        }
    }

    public function testRequiredExtensionsAreListedWithoutPrefix(): void
    {
        $extensions = $this->invoke('getRequiredExtensions');

        $this->assertContains('mbstring', $extensions);
        $this->assertContains('zip', $extensions);

        foreach ($extensions as $extension) {
            $this->assertStringStartsNotWith('ext-', $extension);
        }
    }

    public function testInstalledDependenciesAreIndexedByName(): void
    {
        $dependencies = $this->invoke('getDependencies');

        $this->assertArrayHasKey('league/commonmark', $dependencies);
        $this->assertSame('2.4.0', $dependencies['league/commonmark']['version']);
    }

    public function testResponseHeadersAreIndexedByName(): void
    {
        $headers = $this->invoke('getHeaders');

        $this->assertIsArray($headers);

        foreach ($headers as $name => $value) {
            $this->assertIsString($name);
            $this->assertSame(trim($value), $value);
        }
    }

    public function testPagesAreDisplayed(): void
    {
        $path = TESTS_TMP_PATH . '/backups';
        FileSystem::createDirectory($path);
        FileSystem::write($path . '/backup.zip', 'zip');

        $backupper = new Backupper(new Request(), ['path' => $path, 'name' => 'backup', 'maxExecutionTime' => 60, 'maxFiles' => 10, 'ignore' => []], App::instance()->rootPath(), App::instance());

        $responses = [
            $this->controller()->updates(),
            $this->controller()->backups($backupper),
            $this->controller()->info(),
            $this->makeController(ToolsController::class, system: ['debug' => ['enabled' => true]])->info(),
        ];

        foreach ($responses as $response) {
            $this->assertOutcome(['status' => 200], $response);
        }
    }

    /**
     * @param array<string, string> $query
     */
    #[DataProvider('channelSelectionProvider')]
    public function testLogsChannelSelectionRedirectsToTheChannelKeepingTheFilters(array $query, string $expected): void
    {
        $this->writeLog('first.log', []);
        $this->useLogs([['path' => $this->logPath('first.log'), 'channels' => ['default', 'cache']]]);

        $response = $this->call($this->logsController($query), 'logs');

        $this->assertOutcome(['status' => 302, 'to' => $expected], $response);
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function channelSelectionProvider(): iterable
    {
        yield 'a known channel' => [['channel' => 'cache'], 'panel.tools.logs/cache/'];
        yield 'an unknown channel' => [['channel' => 'missing'], 'panel.tools.logs/default/'];
        yield 'a limit and a level' => [['channel' => 'cache', 'limit' => '100', 'level' => 'error'], 'panel.tools.logs/cache/?limit=100&level=error'];
        yield 'the default limit' => [['channel' => 'cache', 'limit' => '50'], 'panel.tools.logs/cache/'];
        yield 'an empty level' => [['channel' => 'cache', 'level' => ''], 'panel.tools.logs/cache/'];
    }

    public function testLogsOfAnUnknownChannelRedirectToTheDefaultChannel(): void
    {
        $this->writeLog('first.log', []);
        $this->useLogs([['path' => $this->logPath('first.log'), 'channels' => ['default']]]);

        $response = $this->call($this->logsController(), 'logs', ['channel' => 'missing']);

        $this->assertOutcome(['status' => 302, 'to' => 'panel.tools.logs/default/'], $response);
    }

    /**
     * @param array<string, string> $query
     */
    #[DataProvider('logsQueryProvider')]
    public function testLogsAreDisplayed(array $query): void
    {
        $this->writeLog('first.log', [$this->logLine('2026-01-01T10:00:00+00:00', 'default', 'info', 'foo')]);
        $this->useLogs([['path' => $this->logPath('first.log'), 'channels' => ['default']]]);

        $response = $this->call($this->logsController($query), 'logs', ['channel' => 'default']);

        $this->assertOutcome(['status' => 200], $response);
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function logsQueryProvider(): iterable
    {
        yield 'without filters' => [[]];
        yield 'with a limit and a level' => [['limit' => '100', 'level' => 'error']];
        yield 'with a limit that is not allowed' => [['limit' => '7']];
        yield 'with an unknown level' => [['level' => 'missing']];
    }

    public function testClearingLogsDeletesTheEntriesAndNotifiesIt(): void
    {
        $this->writeLog('first.log', [$this->logLine('2026-01-01T10:00:00+00:00', 'default', 'info', 'foo')]);
        $this->useLogs([['path' => $this->logPath('first.log'), 'channels' => ['default']]]);

        $response = $this->call($this->logsController(), 'clearLogs', ['channel' => 'default']);

        $this->assertOutcome(['status' => 302, 'to' => 'panel.tools.logs/default/', 'notices' => ['success']], $response);
        $this->assertFileDoesNotExist($this->logPath('first.log'));
    }

    public function testClearingLogsOfAnUnknownChannelPreservesThemWithoutNotifications(): void
    {
        $this->writeLog('first.log', [$this->logLine('2026-01-01T10:00:00+00:00', 'default', 'info', 'foo')]);
        $this->useLogs([['path' => $this->logPath('first.log'), 'channels' => ['default']]]);

        $response = $this->call($this->logsController(), 'clearLogs', ['channel' => 'missing']);

        $this->assertOutcome(['status' => 302, 'to' => 'panel.tools.logs/default/', 'notices' => []], $response);
        $this->assertFileExists($this->logPath('first.log'));
    }

    public function testLogChannelsListTheDefaultOneFirstWithoutDuplicates(): void
    {
        $this->writeLog('first.log', []);
        $this->writeLog('second.log', []);
        $this->useLogs([
            ['path' => $this->logPath('first.log'), 'channels' => ['cache', 'default']],
            ['path' => $this->logPath('second.log'), 'channels' => ['cache', 'panel']],
        ]);

        $this->assertSame(['default', 'cache', 'panel'], $this->invokeLogs('getLogChannels'));
    }

    public function testLogChannelsOfMissingFilesAndOtherHandlersAreNotListed(): void
    {
        $this->writeLog('first.log', []);
        $this->useLogs([
            ['path' => $this->logPath('first.log'), 'channels' => ['foo']],
            ['path' => $this->logPath('missing.log'), 'channels' => ['bar']],
            ['type' => 'stderr', 'path' => $this->logPath('first.log'), 'channels' => ['baz']],
        ]);

        $this->assertSame(['default', 'foo'], $this->invokeLogs('getLogChannels'));
    }

    public function testLogEntriesAreReadMostRecentFirst(): void
    {
        $this->writeLog('first.log', [
            $this->logLine('2026-01-01T10:00:00+00:00', 'default', 'info', 'first'),
            $this->logLine('2026-01-01T12:00:00+00:00', 'default', 'error', 'third'),
            $this->logLine('2026-01-01T11:00:00+00:00', 'default', 'warning', 'second'),
        ]);
        $this->useLogs([['path' => $this->logPath('first.log')]]);

        $entries = $this->invokeLogs('readLogEntries', 'default', 50);

        $this->assertSame(['third', 'second', 'first'], array_column($entries, 'message'));
        $this->assertSame(['error', 'warning', 'info'], array_column($entries, 'level'));
        $this->assertSame(strtotime('2026-01-01T12:00:00+00:00'), $entries[0]['timestamp']);
    }

    public function testLogEntriesAreLimitedToTheMostRecentOnes(): void
    {
        $this->writeLog('first.log', [
            $this->logLine('2026-01-01T10:00:00+00:00', 'default', 'info', 'first'),
            $this->logLine('2026-01-01T11:00:00+00:00', 'default', 'info', 'second'),
            $this->logLine('2026-01-01T12:00:00+00:00', 'default', 'info', 'third'),
        ]);
        $this->useLogs([['path' => $this->logPath('first.log')]]);

        $this->assertSame(['third', 'second'], array_column($this->invokeLogs('readLogEntries', 'default', 2), 'message'));
    }

    public function testLogEntriesAreFilteredByChannelAndLevel(): void
    {
        $this->writeLog('first.log', [
            $this->logLine('2026-01-01T10:00:00+00:00', 'default', 'info', 'first'),
            $this->logLine('2026-01-01T11:00:00+00:00', 'cache', 'info', 'second'),
            $this->logLine('2026-01-01T12:00:00+00:00', 'default', 'error', 'third'),
        ]);
        $this->useLogs([['path' => $this->logPath('first.log')]]);

        $this->assertSame(['third', 'first'], array_column($this->invokeLogs('readLogEntries', 'default', 50), 'message'));
        $this->assertSame(['third'], array_column($this->invokeLogs('readLogEntries', 'default', 50, 'error'), 'message'));
        $this->assertSame([], $this->invokeLogs('readLogEntries', 'default', 50, 'debug'));
    }

    public function testLogEntriesOfTheHandlersOfTheChannelAreMerged(): void
    {
        $this->writeLog('first.log', [$this->logLine('2026-01-01T10:00:00+00:00', 'default', 'info', 'first')]);
        $this->writeLog('second.log', [$this->logLine('2026-01-01T11:00:00+00:00', 'default', 'info', 'second')]);
        $this->writeLog('third.log', [$this->logLine('2026-01-01T12:00:00+00:00', 'default', 'info', 'third')]);
        $this->useLogs([
            ['path' => $this->logPath('first.log'), 'channels' => ['default']],
            ['path' => $this->logPath('second.log')],
            ['path' => $this->logPath('third.log'), 'channels' => ['cache']],
        ]);

        $this->assertSame(['second', 'first'], array_column($this->invokeLogs('readLogEntries', 'default', 50), 'message'));
    }

    public function testInvalidLogLinesAreIgnoredWhenReading(): void
    {
        $this->writeLog('first.log', [
            'foo',
            '{"datetime":"2026-01-01T10:00:00+00:00","level":"info"}',
            $this->logLine('2026-01-01T11:00:00+00:00', 'default', 'info', 'first'),
            '',
        ]);
        $this->useLogs([['path' => $this->logPath('first.log')]]);

        $this->assertSame(['first'], array_column($this->invokeLogs('readLogEntries', 'default', 50), 'message'));
    }

    public function testJsonLogLinesAreParsed(): void
    {
        $line = $this->logLine('2026-01-01T10:00:00+00:00', 'cache', 'ERROR', 'foo', ['key' => 'value']);

        $this->assertSame([
            'timestamp' => strtotime('2026-01-01T10:00:00+00:00'),
            'channel'   => 'cache',
            'level'     => 'error',
            'message'   => 'foo',
            'context'   => '{"key":"value"}',
        ], $this->invokeLogs('parseLogLine', $line, 'json'));
    }

    public function testLogLinesWithoutChannelBelongToTheDefaultChannel(): void
    {
        $json = Json::encode(['datetime' => '2026-01-01T10:00:00+00:00', 'level' => 'info', 'message' => 'foo']);

        $this->assertSame('default', $this->invokeLogs('parseLogLine', $json, 'json')['channel']);
        $this->assertSame('default', $this->invokeLogs('parseLogLine', '[2026-01-01 10:00:00.000000] INFO: foo', 'text')['channel']);
    }

    public function testTextLogLinesAreParsed(): void
    {
        $entry = $this->invokeLogs('parseLogLine', '[2026-01-01 10:00:00.000000] cache.WARNING: foo {"key":"value"}', 'text');

        $this->assertSame('cache', $entry['channel']);
        $this->assertSame('warning', $entry['level']);
        $this->assertSame('foo {"key":"value"}', $entry['message']);
        $this->assertSame(strtotime('2026-01-01 10:00:00'), $entry['timestamp']);
    }

    #[DataProvider('invalidLogLineProvider')]
    public function testInvalidLogLinesAreRejected(string $line, string $formatter): void
    {
        $this->assertNull($this->invokeLogs('parseLogLine', $line, $formatter));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidLogLineProvider(): iterable
    {
        yield 'text that is not JSON' => ['foo', 'json'];
        yield 'JSON without a message' => ['{"datetime":"2026-01-01T10:00:00+00:00","level":"info"}', 'json'];
        yield 'JSON with a level that is not a string' => ['{"datetime":"2026-01-01T10:00:00+00:00","level":1,"message":"foo"}', 'json'];
        yield 'text that is not a log line' => ['foo', 'text'];
        yield 'JSON in a text log' => ['{"datetime":"2026-01-01T10:00:00+00:00","level":"info","message":"foo"}', 'text'];
    }

    public function testClearingLogEntriesPreservesTheOnesOfOtherChannels(): void
    {
        $other = $this->logLine('2026-01-01T11:00:00+00:00', 'cache', 'info', 'second');
        $this->writeLog('first.log', [
            $this->logLine('2026-01-01T10:00:00+00:00', 'default', 'info', 'first'),
            $other,
        ]);
        $this->useLogs([['path' => $this->logPath('first.log')]]);

        $this->invokeLogs('clearLogEntries', 'default');

        $this->assertSame($other . PHP_EOL, FileSystem::read($this->logPath('first.log')));
    }

    public function testLogFilesWithoutEntriesLeftAreDeleted(): void
    {
        $this->writeLog('first.log', [$this->logLine('2026-01-01T10:00:00+00:00', 'default', 'info', 'first')]);
        $this->useLogs([['path' => $this->logPath('first.log')]]);

        $this->invokeLogs('clearLogEntries', 'default');

        $this->assertFileDoesNotExist($this->logPath('first.log'));
    }

    public function testClearingLogEntriesDeletesInvalidLines(): void
    {
        $other = $this->logLine('2026-01-01T11:00:00+00:00', 'cache', 'info', 'second');
        $this->writeLog('first.log', ['foo', $other]);
        $this->useLogs([['path' => $this->logPath('first.log')]]);

        $this->invokeLogs('clearLogEntries', 'default');

        $this->assertSame($other . PHP_EOL, FileSystem::read($this->logPath('first.log')));
    }

    public function testClearingLogEntriesPreservesTheFilesOfOtherChannels(): void
    {
        $this->writeLog('first.log', [$this->logLine('2026-01-01T10:00:00+00:00', 'default', 'info', 'first')]);
        $this->writeLog('second.log', [$this->logLine('2026-01-01T11:00:00+00:00', 'cache', 'info', 'second')]);
        $this->useLogs([
            ['path' => $this->logPath('first.log'), 'channels' => ['default']],
            ['path' => $this->logPath('second.log'), 'channels' => ['cache']],
        ]);

        $this->invokeLogs('clearLogEntries', 'default');

        $this->assertFileDoesNotExist($this->logPath('first.log'));
        $this->assertFileExists($this->logPath('second.log'));
    }

    private function controller(): ToolsController
    {
        return $this->makeController(ToolsController::class);
    }

    private function invoke(string $method): mixed
    {
        $controller = $this->controller();

        return (new ReflectionMethod($controller, $method))->invoke($controller);
    }

    /**
     * @param array<string, string> $query
     */
    private function logsController(array $query = []): ToolsController
    {
        $router = $this->createStub(Router::class);
        $router->method('generate')->willReturnCallback(static fn(string $name, array $params = []): string => '/route/' . $name . '/' . ($params['channel'] ?? ''));

        return $this->makeController(ToolsController::class, system: ['logs' => ['handlers' => $this->logHandlers]], services: [Router::class => $router], query: $query);
    }

    /**
     * Use the given file handlers, defined with the type `file` unless specified
     *
     * @param list<array<string, mixed>> $handlers
     */
    private function useLogs(array $handlers): void
    {
        $this->logHandlers = [];

        foreach ($handlers as $index => $handler) {
            $this->logHandlers["handler{$index}"] = ['type' => 'file', ...$handler];
        }
    }

    private function logPath(string $name): string
    {
        return FileSystem::joinPaths(TESTS_TMP_PATH, 'logs', $name);
    }

    /**
     * @param list<string> $lines
     */
    private function writeLog(string $name, array $lines): void
    {
        FileSystem::write($this->logPath($name), $lines === [] ? '' : implode(PHP_EOL, $lines) . PHP_EOL);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logLine(string $datetime, string $channel, string $level, string $message, array $context = []): string
    {
        return Json::encode(['datetime' => $datetime, 'channel' => $channel, 'level' => $level, 'message' => $message, 'context' => $context]);
    }

    private function invokeLogs(string $method, mixed ...$arguments): mixed
    {
        $controller = $this->logsController();

        return (new ReflectionMethod($controller, $method))->invoke($controller, ...$arguments);
    }
}
