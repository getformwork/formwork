<?php

namespace Formwork\Backup;

use Formwork\Backup\Utils\ZipErrors;
use Formwork\Exceptions\TranslatedException;
use Formwork\Utils\FileSystem;
use RuntimeException;
use ZipArchive;

final class Backupper
{
    /**
     * Date format used in backup archive name
     */
    private const string DATE_FORMAT = 'YmdHis';

    /**
     * @param array<mixed> $options
     */
    public function __construct(
        private array $options,
    ) {
        if (!extension_loaded('zip')) {
            throw new RuntimeException(sprintf('Class %s requires the extension "zip" to be enabled', self::class));
        }
    }

    /**
     * Make a backup of all site files
     *
     * @return string Backup archive file path
     */
    public function backup(): string
    {
        $previousMaxExecutionTime = ini_set('max_execution_time', $this->options['maxExecutionTime']);

        $source = ROOT_PATH;

        $path = $this->options['path'];
        if (!FileSystem::exists($this->options['path'])) {
            FileSystem::createDirectory($this->options['path'], recursive: true);
        }

        $name = sprintf('%s-%s-%s.zip', str_replace([' ', '.'], '-', $this->options['hostname'] ?? 'unknown-host'), $this->options['name'], date(self::DATE_FORMAT));

        $destination = FileSystem::joinPaths($path, $name);

        $zipArchive = new ZipArchive();

        if (($status = $zipArchive->open($destination, ZipArchive::CREATE)) === true) {
            foreach (FileSystem::listRecursive($source, FileSystem::LIST_ALL) as $file) {
                if ($this->isCopiable($file)) {
                    $zipArchive->addFile(FileSystem::joinPaths($source, $file), $file);
                }
            }
            $zipArchive->close();
        }

        $this->deleteOldBackups();

        if ($previousMaxExecutionTime !== false) {
            ini_set('max_execution_time', $previousMaxExecutionTime);
        }

        if (is_int($status) && $status !== ZipArchive::ER_OK) {
            /** @var key-of<ZipErrors::ERROR_MESSAGES> $status */
            throw new TranslatedException(ZipErrors::ERROR_MESSAGES[$status], ZipErrors::ERROR_LANGUAGE_STRINGS[$status]);
        }

        return $destination;
    }

    /**
     * @return array<int, string>
     */
    public function getBackups(): array
    {
        $backups = [];

        if (!FileSystem::exists($this->options['path'])) {
            FileSystem::createDirectory($this->options['path']);
        }

        foreach (FileSystem::listFiles($this->options['path']) as $file) {
            $date = FileSystem::lastModifiedTime(FileSystem::joinPaths($this->options['path'], $file));
            $backups[$date] = FileSystem::joinPaths($this->options['path'], $file);
        }

        krsort($backups);

        return $backups;
    }

    /**
     * Return whether a file is copiable in the backup archive
     */
    private function isCopiable(string $file): bool
    {
        foreach ($this->options['ignore'] as $pattern) {
            if (fnmatch($pattern, $file)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Delete old backups
     */
    private function deleteOldBackups(): void
    {
        $backups = [];

        foreach (FileSystem::listFiles($this->options['path']) as $file) {
            $date = FileSystem::lastModifiedTime(FileSystem::joinPaths($this->options['path'], $file));
            $backups[$date] = FileSystem::joinPaths($this->options['path'], $file);
        }

        ksort($backups);

        $deletableBackups = count($backups) - $this->options['maxFiles'];

        if ($deletableBackups > 0) {
            foreach (array_slice($backups, 0, $deletableBackups) as $backup) {
                FileSystem::delete($backup);
            }
        }
    }
}
