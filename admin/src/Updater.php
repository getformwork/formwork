<?php

namespace Formwork\Admin;

use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;
use RuntimeException;
use ZipArchive;

class Updater
{
    const REPOSITORY = 'giuscris/formwork';

    protected $options;

    protected $data = array(
        'last-check' => null,
        'last-update' => null,
        'etag' => null,
        'release' => null,
        'up-to-date' => false
    );

    protected $context;

    protected $release;

    protected $archiveUri;

    protected $headers;

    protected $upToDate;

    public function __construct($options = array())
    {
        $this->options = array_merge($this->defaults(), $options);

        if (FileSystem::exists($this->options['logFile'])) {
            $this->data = array_merge($this->data, (array) json_decode(FileSystem::read($this->options['logFile']), true));
        }

        $this->context = stream_context_create(array(
            'http' => array('user_agent' => 'PHP Formwork-Updater')
        ));
    }

    public function defaults()
    {
        return array(
            'time' => 3600,
            'force' => false,
            'logFile' => LOGS_PATH . 'updates.json',
            'tempFile' => ROOT_PATH . '.formwork-update.zip',
            'cleanupAfterInstall' => false,
            'ignore' => array(
                'admin/accounts/*',
                'admin/avatars/*',
                'admin/logs/*',
                'assets/*',
                'config/*',
                'content/*',
                'templates/*'
            )
        );
    }

    public function checkUpdates()
    {
        if (time() - $this->data['last-check'] < $this->options['time']) {
            return $this->data['up-to-date'];
        }

        $this->loadRelease();

        $this->data['release'] = $this->release;

        $this->data['last-check'] = time();

        if (version_compare(Formwork::VERSION, $this->release['tag']) >= 0) {
            $this->data['up-to-date'] = true;
            $this->save();
            return true;
        }

        if (isset($this->getHeaders()['ETag'])) {
            $ETag = trim($this->headers['ETag'], '"');

            if ($this->data['etag'] == $ETag) {
                $this->data['up-to-date'] = true;
                $this->save();
                return true;
            }
        }

        $this->data['up-to-date'] = false;
        $this->save();
        return false;
    }

    public function update()
    {
        $this->checkUpdates();

        if (!$this->options['force'] && $this->data['up-to-date']) {
            return;
        }

        $this->loadRelease();

        FileSystem::download($this->archiveUri, $this->options['tempFile'], true, $this->context);

        if (!FileSystem::exists($this->options['tempFile'])) {
            throw new RuntimeException('Cannot update');
        }

        $zip = new ZipArchive();
        $zip->open($this->options['tempFile']);
        $baseFolder = $zip->getNameIndex(0);

        for ($i = 1; $i < $zip->numFiles; $i++) {
            $source = substr($zip->getNameIndex($i), strlen($baseFolder));
            $destination = ROOT_PATH . $source;
            $destinationDirectory = FileSystem::dirname($destination);

            if ($this->isCopiable($source)) {
                if (!FileSystem::exists($destinationDirectory)) {
                    FileSystem::createDirectory($destinationDirectory);
                }
                if (substr($destination, -1) !== DS) {
                    FileSystem::write($destination, $zip->getFromIndex($i));
                }
                $installedFiles[] = $destination;
            }
        }

        FileSystem::delete($this->options['tempFile']);

        if ($this->options['cleanupAfterInstall']) {
            $deletableFiles = $this->findDeletableFiles($installedFiles);
            if (!empty($deletableFiles)) {
                foreach ($deletableFiles as $file) {
                    FileSystem::delete($file);
                }
            }
        }

        $this->data['last-update'] = time();

        if (isset($this->getHeaders()['ETag'])) {
            $ETag = trim($this->headers['ETag'], '"');
            $this->data['etag'] = $ETag;
        }

        $this->data['up-to-date'] = true;
        $this->save();

        return true;
    }

    public function latestRelease()
    {
        return $this->data['release'];
    }

    protected function loadRelease()
    {
        if (!is_null($this->release)) {
            return;
        }

        $uri = 'https://api.github.com/repos/' . static::REPOSITORY . '/releases/latest';
        $data = json_decode(FileSystem::fetch($uri, $this->context), true);

        if (!$data) {
            throw new RuntimeException('Cannot fetch release data');
        }

        $this->release = array(
            'name' => $data['name'],
            'tag'  => $data['tag_name'],
            'date' => strtotime($data['published_at'])
        );

        $this->archiveUri = $data['zipball_url'];
    }

    protected function getHeaders()
    {
        if (!is_null($this->headers)) {
            return $this->headers;
        }
        $this->headers = get_headers($this->archiveUri, 1, $this->context);
        return $this->headers;
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

    protected function findDeletableFiles($installedFiles)
    {
        $list = array();
        foreach ($installedFiles as $path) {
            $list[] = $path;
            if (FileSystem::exists($path) && FileSystem::isDirectory($path)) {
                foreach (FileSystem::scan($path) as $item) {
                    $item = FileSystem::normalize($path) . $item;
                    if (FileSystem::isDirectory($item) && !empty(FileSystem::scan($item))) {
                        continue;
                    }
                    $list[] = $item;
                }
            }
        }
        return array_diff($list, $installedFiles);
    }

    protected function save()
    {
        FileSystem::write($this->options['logFile'], json_encode($this->data));
    }
}
