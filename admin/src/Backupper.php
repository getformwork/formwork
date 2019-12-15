<?php

namespace Formwork\Admin;

use Formwork\Admin\Exceptions\TranslatedException;
use Formwork\Admin\Utils\ZipErrors;
use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Uri;
use ZipArchive;

class Backupper
{
    /**
     * Date format used in backup archive name
     *
     * @var string
     */
    protected const DATE_FORMAT = 'YmdHis';

    /**
     * Backupper options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Return a new Backupper instance
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->defaults(), $options);
    }

    /**
     * Return an array with default option values
     *
     * @return array
     */
    public function defaults()
    {
        return [
            'maxExecutionTime' => 180,
            'name'             => str_replace([' ', '.'], '-', Uri::host()) . '-formwork-backup',
            'path'             => Formwork::instance()->option('backup.path'),
            'maxFiles'         => Formwork::instance()->option('backup.max_files'),
            'ignore'           => [
                '.git/*',
                '*.DS_Store',
                '*.gitignore',
                '*.gitkeep',
                'admin/node_modules/*',
                'backup/*'
            ]
        ];
    }

    /**
     * Make a backup of all site files
     *
     * @return string Backup archive file path
     */
    public function backup()
    {
        $previousMaxExecutionTime = ini_set('max_execution_time', $this->options['maxExecutionTime']);

        $source = ROOT_PATH;

        $path = $this->options['path'];
        if (!FileSystem::exists($this->options['path'])) {
            FileSystem::createDirectory($this->options['path'], true);
        }

        $destination = $path . $this->options['name'] . '-' . date(self::DATE_FORMAT) . '.zip';

        $files = FileSystem::scanRecursive($source, true);
        $files = array_filter($files, function ($item) use ($source) {
            return $this->isCopiable(substr($item, strlen($source)));
        });

        $zip = new ZipArchive();

        if (($status = $zip->open($destination, ZipArchive::CREATE)) === true) {
            foreach ($files as $file) {
                $zip->addFile($file, substr($file, strlen($source)));
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
     *
     * @return bool
     */
    protected function isCopiable(string $file)
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
    protected function deleteOldBackups()
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
