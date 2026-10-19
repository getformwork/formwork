<?php

namespace Formwork\Tests\Unit\Log;

use DateTimeImmutable;
use Formwork\Log\Formatter\JsonFormatter;
use Formwork\Log\Formatter\TextFormatter;
use Formwork\Log\Handler\AbstractHandler;
use Formwork\Log\Handler\FileHandler;
use Formwork\Log\Handler\StderrHandler;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LogLevel;
use ReflectionProperty;
use Throwable;

#[CoversClass(FileHandler::class)]
#[CoversClass(StderrHandler::class)]
#[UsesTempDirectory]
final class FileHandlerTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = FileSystem::joinPaths(TESTS_TMP_PATH, 'logs', 'app.log');
    }

    public function testHandlerIsAnAbstractHandler(): void
    {
        $this->assertInstanceOf(AbstractHandler::class, new FileHandler($this->file));
    }

    public function testFileIsNotCreatedUntilSomethingIsLogged(): void
    {
        $this->assertInstanceOf(FileHandler::class, new FileHandler($this->file));

        $this->assertFileDoesNotExist($this->file);
        $this->assertDirectoryDoesNotExist(dirname($this->file));
    }

    public function testRecordsAreAppendedOneForLine(): void
    {
        $fileHandler = new FileHandler($this->file, new TextFormatter());

        $fileHandler->handle(new DateTimeImmutable('2025-01-02 03:04:05.000000 UTC'), 'info', 'first', []);
        $fileHandler->handle(new DateTimeImmutable('2025-01-02 03:04:06.000000 UTC'), 'error', 'second', []);

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
        $fileHandler = new FileHandler($this->file, new JsonFormatter(), LogLevel::WARNING);

        $fileHandler->handle(new DateTimeImmutable(), 'info', 'ignored', []);
        $fileHandler->handle(new DateTimeImmutable(), 'debug', 'ignored', []);

        $this->assertFileDoesNotExist($this->file);

        $fileHandler->handle(new DateTimeImmutable(), 'warning', 'kept', []);

        $this->assertFileExists($this->file);
        $this->assertCount(1, file($this->file) ?: []);
    }

    public function testInvalidThresholdsAreRejected(): void
    {
        $invalidArgumentException = $this->captureException(InvalidArgumentException::class, fn() => new FileHandler($this->file, new JsonFormatter(), $this->unchecked('verbose')));

        $this->assertInstanceOf(InvalidArgumentException::class, $invalidArgumentException);
    }

    public function testSeveralHandlersCanWriteToTheSameFile(): void
    {
        $first = new FileHandler($this->file, new TextFormatter(false));
        $second = new FileHandler($this->file, new TextFormatter(false));
        $date = new DateTimeImmutable('2025-01-02 03:04:05.000000 UTC');

        $first->handle($date, 'info', 'one', []);
        $second->handle($date, 'info', 'two', []);
        $first->handle($date, 'info', 'three', []);

        $this->assertCount(3, file($this->file) ?: []);
    }

    public function testRecordsAreFlushedWhenTheHandlerIsDestroyed(): void
    {
        $fileHandler = new FileHandler($this->file, new TextFormatter(false));
        $fileHandler->handle(new DateTimeImmutable('2025-01-02 03:04:05.000000 UTC'), 'info', 'message', []);

        unset($fileHandler);

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
        $fileHandler = new FileHandler(TESTS_TMP_PATH . '/blocked/file/app.log');

        $this->expectException(Throwable::class);
        $fileHandler->handle(new DateTimeImmutable(), 'info', 'message', []);
    }

    public function testStderrHandlerWritesToStderrWithTheTextFormatter(): void
    {
        $stderrHandler = new StderrHandler();

        $path = new ReflectionProperty(FileHandler::class, 'path');
        $formatter = new ReflectionProperty(AbstractHandler::class, 'formatter');

        $this->assertInstanceOf(FileHandler::class, $stderrHandler);
        $this->assertSame('php://stderr', $path->getValue($stderrHandler));
        $this->assertInstanceOf(TextFormatter::class, $formatter->getValue($stderrHandler));
    }

    public function testStderrHandlerAcceptsAFormatterAndALevel(): void
    {
        $stderrHandler = new StderrHandler(new JsonFormatter(), LogLevel::ERROR);

        $formatter = new ReflectionProperty(AbstractHandler::class, 'formatter');
        $level = new ReflectionProperty(AbstractHandler::class, 'level');

        $this->assertInstanceOf(JsonFormatter::class, $formatter->getValue($stderrHandler));
        $this->assertSame('error', $level->getValue($stderrHandler));
    }

    public function testStderrHandlerRejectsInvalidLevels(): void
    {
        $invalidArgumentException = $this->captureException(InvalidArgumentException::class, fn() => new StderrHandler(new TextFormatter(), $this->unchecked('verbose')));

        $this->assertInstanceOf(InvalidArgumentException::class, $invalidArgumentException);
    }
}
