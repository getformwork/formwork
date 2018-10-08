<?php

namespace Formwork\Admin;

use Formwork\Admin\Exceptions\LocalizedException;
use Formwork\Admin\Utils\ZipErrors;
use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;
use ZipArchive;

class Backupper
{
    const DATE_FORMAT = 'YmdHis';

    protected $options;

    public function __construct($options = array())
    {
        $this->options = array_merge($this->defaults(), $options);
    }

    public function defaults()
    {
        return array(
            'maxExecutionTime' => 180,
            'name'             => 'formwork-backup',
            'path'             => Formwork::instance()->option('backup.path'),
            'ignore'           => array(
                '.git/*',
                '*.DS_Store',
                '*.gitignore',
                '*.gitkeep',
                'admin/node_modules/*',
                'backup/*'
            )
        );
    }

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

        if ($previousMaxExecutionTime !== false) {
            ini_set('max_execution_time', $previousMaxExecutionTime);
        }

        if (is_int($status) && $status !== ZipArchive::ER_OK) {
            throw new LocalizedException(ZipErrors::$errorMessages[$status], ZipErrors::$errorLanguageStrings[$status]);
        }

        return $destination;
    }

    protected function isCopiable($file)
    {
        foreach ($this->options['ignore'] as $pattern) {
            if (fnmatch($pattern, $file)) {
                return false;
            }
        }
        return true;
    }
}
