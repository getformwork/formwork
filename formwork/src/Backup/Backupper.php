<?php

namespace Formwork\Backup;

use Formwork\Backup\Utils\ZipErrors;
use Formwork\Cms\App;
use Formwork\Exceptions\TranslatedException;
use Formwork\Http\Request;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use RuntimeException;
use UnexpectedValueException;
use ZipArchive;

final class Backupper
{
    /**
     * Date format used in backup archive name
     */
    private const string DATE_FORMAT = 'Ymd-His';

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private Request $request,
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
    public function backup(?string $name = null, ?string $hostname = null): string
    {
        $previousMaxExecutionTime = ini_set('max_execution_time', $this->options['maxExecutionTime']);

        try {
            $source = ROOT_PATH;

            $path = $this->options['path'];
            if (!FileSystem::exists($this->options['path'])) {
                FileSystem::createDirectory($this->options['path'], recursive: true);
            }

            $date = date(self::DATE_FORMAT);

            $suffix = "-{$date}.zip";

            $name = Str::interpolate($name ?? $this->options['name'], [
                'hostname' => preg_replace(['/\.|:+/', '/\[|\]/'], ['-', ''], $hostname ?? $this->options['hostname'] ?? $this->request->host() ?? 'unknown-host'),
                'site'     => Str::slug(App::instance()->site()->title() ?? 'unknown-site'),
                'context'  => PHP_SAPI === 'cli' ? 'cli' : 'web',
                'version'  => App::VERSION,
                'random'   => FileSystem::randomName(),
            ]);

            $filename = rtrim(substr($name, 0, 75 - strlen($suffix)), '-_') . $suffix;

            if (!preg_match('/^[A-Za-z0-9._-]+$/', $filename)) {
                throw new UnexpectedValueException(sprintf('Backup name "%s" contains invalid characters. Backup names can only contain letters, numbers, dots, underscores and hyphens.', $filename));
            }

            $destination = FileSystem::joinPaths($path, $filename);

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

            if (is_int($status) && $status !== ZipArchive::ER_OK) {
                /** @var key-of<ZipErrors::ERROR_MESSAGES> $status */
                throw new TranslatedException(ZipErrors::ERROR_MESSAGES[$status], ZipErrors::ERROR_LANGUAGE_STRINGS[$status]);
            }

            return $destination;
        } finally {
            if ($previousMaxExecutionTime !== false) {
                ini_set('max_execution_time', $previousMaxExecutionTime);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    public function getBackups(): array
    {
        if (!FileSystem::exists($this->options['path'])) {
            FileSystem::createDirectory($this->options['path'], true);
        }

        $backups = $this->collectBackups();

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
     * Collect backup files indexed by modification time
     *
     * @return array<int, string>
     */
    private function collectBackups(): array
    {
        $backups = [];

        foreach (FileSystem::listFiles($this->options['path']) as $file) {
            $path = FileSystem::joinPaths($this->options['path'], $file);
            $date = FileSystem::lastModifiedTime($path);

            // Files sharing the same modification time get the closest earlier free timestamp
            while (isset($backups[$date])) {
                $date--;
            }

            $backups[$date] = $path;
        }

        return $backups;
    }

    /**
     * Delete old backups
     */
    private function deleteOldBackups(): void
    {
        $backups = $this->collectBackups();

        ksort($backups);

        $deletableBackups = count($backups) - $this->options['maxFiles'];

        if ($deletableBackups > 0) {
            foreach (array_slice($backups, 0, $deletableBackups) as $backup) {
                FileSystem::delete($backup);
            }
        }
    }
}
