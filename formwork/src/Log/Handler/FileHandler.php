<?php

namespace Formwork\Log\Handler;

use DateTimeInterface;
use Formwork\Log\Formatter\FormatterInterface;
use Formwork\Log\Formatter\JsonFormatter;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use Psr\Log\LogLevel;
use RuntimeException;

class FileHandler extends AbstractHandler
{
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

        if (
            !Str::startsWith($this->path, 'php://')
            && !FileSystem::isDirectory($directory = dirname($this->path), assertExists: false)
        ) {
            FileSystem::createDirectory($directory, recursive: true);
        }

        if (($handle = fopen($this->path, 'a')) === false) {
            throw new RuntimeException(sprintf('Unable to open the stream "%s" for writing in append mode', $this->path));
        }

        $locked = flock($handle, LOCK_EX);

        fwrite($handle, $line . PHP_EOL);

        if ($locked) {
            flock($handle, LOCK_UN);
        }

        fclose($handle);
    }
}
