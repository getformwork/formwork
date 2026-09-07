<?php

namespace Formwork\Log\Handler;

use DateTimeInterface;
use Formwork\Log\Formatter\FormatterInterface;
use Formwork\Log\Formatter\JsonFormatter;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use Psr\Log\LogLevel;
use RuntimeException;

/**
 * @since 2.3.0
 */
class FileHandler extends AbstractHandler
{
    /**
     * @var resource
     */
    protected $handle;

    /**
     * @inheritDoc
     */
    public function __construct(
        protected string $path,
        FormatterInterface $formatter = new JsonFormatter(),
        string $level = LogLevel::DEBUG,
    ) {
        parent::__construct($formatter, $level);
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @inheritDoc
     *
     * @throws RuntimeException If the log stream cannot be opened for writing
     */
    public function handle(DateTimeInterface $datetime, string $level, string $message, array $context): void
    {
        if (!$this->shouldHandle($level)) {
            return;
        }

        $line = $this->formatter->format($datetime, $level, $message, $context);

        $this->write($line);
    }

    /**
     * Open the log stream for writing
     */
    protected function open(): void
    {
        if (
            !Str::contains($this->path, '://')
            && !FileSystem::isDirectory($directory = dirname($this->path), assertExists: false)
        ) {
            FileSystem::createDirectory($directory, recursive: true);
        }

        if (($handle = fopen($this->path, 'a')) === false) {
            throw new RuntimeException(sprintf('Unable to open the stream "%s" for writing in append mode', $this->path));
        }

        $this->handle = $handle;
    }

    /**
     * Write a line to the log stream
     */
    protected function write(string $line): void
    {
        $data = $line . PHP_EOL;

        if (!is_resource($this->handle)) {
            $this->open();
        }

        $locked = flock($this->handle, LOCK_EX);

        $bytesWritten = fwrite($this->handle, $data);

        if ($locked) {
            flock($this->handle, LOCK_UN);
        }

        if ($bytesWritten === false || $bytesWritten < strlen($data)) {
            $this->close();
            throw new RuntimeException(sprintf('Unable to write log data to stream "%s"', $this->path));
        }
    }

    /**
     * Close the log stream
     */
    protected function close(): void
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }
}
