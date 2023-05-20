<?php

namespace Formwork\Panel;

use Formwork\Exceptions\TranslatedException;
use Formwork\Formwork;
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
     */
    protected array $options = [];

    /**
     * Return a new Backupper instance
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->defaults(), $options);
    }

    /**
     * Return an array with default option values
     */
    public function defaults(): array
    {
        return [
            'maxExecutionTime' => 180,
            'name'             => str_replace([' ', '.'], '-', Uri::host()) . '-formwork-backup',
            'path'             => Formwork::instance()->config()->get('backup.path'),
            'maxFiles'         => Formwork::instance()->config()->get('backup.maxFiles'),
            'ignore'           => [
                '.git/*',
                '*.DS_Store',
                '*.gitignore',
                '*.gitkeep',
                'panel/node_modules/*',
                'backup/*',
            ],
        ];
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
            FileSystem::createDirectory($this->options['path'], true);
        }

        $destination = $path . $this->options['name'] . '-' . date(self::DATE_FORMAT) . '.zip';

        $zip = new ZipArchive();

        if (($status = $zip->open($destination, ZipArchive::CREATE)) === true) {
            foreach (FileSystem::listRecursive($source, FileSystem::LIST_ALL) as $file) {
                if ($this->isCopiable($file)) {
                    $zip->addFile($file, $file);
                }
            }
            $zip->close();
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
            $date = FileSystem::lastModifiedTime($this->options['path'] . $file);
            $backups[$date] = $this->options['path'] . $file;
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
