<?php

namespace Formwork;

use Formwork\Config\Config;
use Formwork\Exceptions\TranslatedException;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Uri;
use Formwork\Utils\ZipErrors;
use ZipArchive;

class Backupper
{
    /**
     * Date format used in backup archive name
     */
    protected const DATE_FORMAT = 'YmdHis';

    /**
     * Backupper options
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * Return a new Backupper instance
     */
    public function __construct(Config $config)
    {
        $this->options = $config->get('system.backup');
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

        $name = sprintf('%s-%s-%s.zip', str_replace([' ', '.'], '-', Uri::host() ?? ''), $this->options['name'], date(self::DATE_FORMAT));

        $destination = FileSystem::joinPaths($path, $name);

        $zipArchive = new ZipArchive();

        if (($status = $zipArchive->open($destination, ZipArchive::CREATE)) === true) {
            foreach (FileSystem::listRecursive($source, FileSystem::LIST_ALL) as $file) {
                if ($this->isCopiable($file)) {
                    $zipArchive->addFile($file, $file);
                }
            }
            $zipArchive->close();
        }

        $this->deleteOldBackups();

        if ($previousMaxExecutionTime !== false) {
            ini_set('max_execution_time', $previousMaxExecutionTime);
        }

        if (is_int($status) && $status !== ZipArchive::ER_OK) {
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
    protected function isCopiable(string $file): bool
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
    protected function deleteOldBackups(): void
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
