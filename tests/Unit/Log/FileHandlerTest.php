<?php

namespace Formwork\Tests\Unit\Log;

use DateTimeImmutable;
use Formwork\Log\Formatter\JsonFormatter;
use Formwork\Log\Formatter\TextFormatter;
use Formwork\Log\Handler\AbstractHandler;
use Formwork\Log\Handler\FileHandler;
use Formwork\Log\Handler\StderrHandler;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LogLevel;
use ReflectionProperty;

#[CoversClass(FileHandler::class)]
#[CoversClass(StderrHandler::class)]
final class FileHandlerTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->file = FileSystem::joinPaths(TESTS_TMP_PATH, 'logs', 'app.log');
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testHandlerIsAnAbstractHandler(): void
    {
        $this->assertInstanceOf(AbstractHandler::class, new FileHandler($this->file));
    }

    public function testFileIsNotCreatedUntilSomethingIsLogged(): void
    {
        new FileHandler($this->file);

        $this->assertFileDoesNotExist($this->file);
        $this->assertDirectoryDoesNotExist(dirname($this->file));
    }

    public function testRecordsAreAppendedOneForLine(): void
    {
        $handler = new FileHandler($this->file, new TextFormatter());

        $handler->handle(new DateTimeImmutable('2025-01-02 03:04:05.000000 UTC'), 'info', 'first', []);
        $handler->handle(new DateTimeImmutable('2025-01-02 03:04:06.000000 UTC'), 'error', 'second', []);

        $this->assertSame(
            "[2025-01-02 03:04:05.000000] INFO: first\n[2025-01-02 03:04:06.000000] ERROR: second\n",
            FileSystem::read($this->file),
        );
    }

    public function testMissingDirectoriesAreCreatedRecursively(): void
    {
        $file = FileSystem::joinPaths(TESTS_TMP_PATH, 'deeply', 'nested', 'logs', 'app.log');

        (new FileHandler($file))->handle(new DateTimeImmutable(), 'info', 'message', []);

        $this->assertFileExists($file);
    }

    public function testExistingContentIsPreserved(): void
    {
        FileSystem::createDirectory(dirname($this->file));
        FileSystem::write($this->file, "existing line\n");

        (new FileHandler($this->file, new TextFormatter(false)))->handle(new DateTimeImmutable('2025-01-02 03:04:05.000000 UTC'), 'info', 'new', []);

        $this->assertSame("existing line\n[2025-01-02 03:04:05.000000] INFO: new\n", FileSystem::read($this->file));
    }

    public function testJsonFormatterIsTheDefault(): void
    {
        (new FileHandler($this->file))->handle(new DateTimeImmutable('2025-01-02 03:04:05.000000 UTC'), 'info', 'message', ['a' => 1]);

        $entry = json_decode(trim(FileSystem::read($this->file)), true);

        $this->assertSame('message', $entry['message']);
        $this->assertSame(['a' => 1], $entry['context']);
    }

    public function testCustomFormattersAreUsed(): void
    {
        (new FileHandler($this->file, new TextFormatter()))->handle(new DateTimeImmutable('2025-01-02 03:04:05.000000 UTC'), 'info', 'message', []);

        $this->assertStringStartsWith('[2025-01-02 03:04:05.000000] INFO: message', FileSystem::read($this->file));
    }

    public function testRecordsBelowTheThresholdAreNotWritten(): void
    {
        $handler = new FileHandler($this->file, new JsonFormatter(), LogLevel::WARNING);

        $handler->handle(new DateTimeImmutable(), 'info', 'ignored', []);
        $handler->handle(new DateTimeImmutable(), 'debug', 'ignored', []);

        $this->assertFileDoesNotExist($this->file);

        $handler->handle(new DateTimeImmutable(), 'warning', 'kept', []);

        $this->assertFileExists($this->file);
        $this->assertCount(1, file($this->file));
    }

    public function testInvalidThresholdsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FileHandler($this->file, new JsonFormatter(), 'verbose');
    }

    public function testSeveralHandlersCanWriteToTheSameFile(): void
    {
        $first = new FileHandler($this->file, new TextFormatter(false));
        $second = new FileHandler($this->file, new TextFormatter(false));
        $date = new DateTimeImmutable('2025-01-02 03:04:05.000000 UTC');

        $first->handle($date, 'info', 'one', []);
        $second->handle($date, 'info', 'two', []);
        $first->handle($date, 'info', 'three', []);

        $this->assertCount(3, file($this->file));
    }

    public function testRecordsAreFlushedWhenTheHandlerIsDestroyed(): void
    {
        $handler = new FileHandler($this->file, new TextFormatter(false));
        $handler->handle(new DateTimeImmutable('2025-01-02 03:04:05.000000 UTC'), 'info', 'message', []);

        unset($handler);

        $this->assertStringContainsString('message', FileSystem::read($this->file));
    }

    public function testLongRecordsAreWrittenCompletely(): void
    {
        $message = str_repeat('x', 100000);

        (new FileHandler($this->file, new TextFormatter(false)))->handle(new DateTimeImmutable(), 'info', $message, []);

        $this->assertStringContainsString($message . "\n", FileSystem::read($this->file));
    }

    public function testUnwritableLocationsAreReported(): void
    {
        // The parent of the log file is a regular file
        FileSystem::createDirectory(TESTS_TMP_PATH . '/blocked');
        FileSystem::write(TESTS_TMP_PATH . '/blocked/file', 'content');
        $handler = new FileHandler(TESTS_TMP_PATH . '/blocked/file/app.log');

        $this->expectException(\Throwable::class);
        $handler->handle(new DateTimeImmutable(), 'info', 'message', []);
    }

    public function testStderrHandlerWritesToStderrWithTheTextFormatter(): void
    {
        $handler = new StderrHandler();

        $path = new ReflectionProperty(FileHandler::class, 'path');
        $formatter = new ReflectionProperty(AbstractHandler::class, 'formatter');

        $this->assertInstanceOf(FileHandler::class, $handler);
        $this->assertSame('php://stderr', $path->getValue($handler));
        $this->assertInstanceOf(TextFormatter::class, $formatter->getValue($handler));
    }

    public function testStderrHandlerAcceptsAFormatterAndALevel(): void
    {
        $handler = new StderrHandler(new JsonFormatter(), LogLevel::ERROR);

        $formatter = new ReflectionProperty(AbstractHandler::class, 'formatter');
        $level = new ReflectionProperty(AbstractHandler::class, 'level');

        $this->assertInstanceOf(JsonFormatter::class, $formatter->getValue($handler));
        $this->assertSame('error', $level->getValue($handler));
    }

    public function testStderrHandlerRejectsInvalidLevels(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StderrHandler(new TextFormatter(), 'verbose');
    }
}
