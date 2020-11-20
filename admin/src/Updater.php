<?php

namespace Formwork\Admin;

use Formwork\Admin\Utils\Registry;
use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use RuntimeException;
use ZipArchive;

class Updater
{
    /**
     * GitHub repository from which updates are retrieved
     *
     * @var string
     */
    protected const REPOSITORY = 'getformwork/formwork';

    /**
     * GitHub API latest release URI
     *
     * @var string
     */
    protected const API_RELEASE_URI = 'https://api.github.com/repos/' . self::REPOSITORY . '/releases/latest';

    /**
     * Updater options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Updates registry
     *
     * @var Registry
     */
    protected $registry;

    /**
     * Updates registry default data
     *
     * @var array
     */
    protected $registryDefaults = [
        'last-check'  => null,
        'last-update' => null,
        'etag'        => null,
        'release'     => null,
        'up-to-date'  => false
    ];

    /**
     * Stream context to make HTTP(S) requests
     *
     * @var resource
     */
    protected $context;

    /**
     * Array containing release information
     *
     * @var array
     */
    protected $release;

    /**
     * Headers to send in HTTP(S) requests
     *
     * @var array
     */
    protected $headers;

    /**
     * Whether Formwork is up-to-date
     *
     * @var bool
     */
    protected $upToDate;

    /**
     * Create a new Updater instance
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->defaults(), $options);

        $this->registry = new Registry($this->options['registryFile']);

        if (empty($this->registry->toArray())) {
            $this->initializeRegistry();
        }

        $this->context = stream_context_create([
            'http' => ['user_agent' => 'PHP Formwork-Updater']
        ]);
    }

    /**
     * Return updater default options
     */
    public function defaults(): array
    {
        return [
            'time'                => 900,
            'force'               => false,
            'registryFile'        => Admin::LOGS_PATH . 'updates.json',
            'tempFile'            => ROOT_PATH . '.formwork-update.zip',
            'preferDistAssets'    => false,
            'cleanupAfterInstall' => false,
            'ignore'              => [
                'admin/accounts/*',
                'admin/avatars/*',
                'admin/logs/*',
                'assets/*',
                'backup/*',
                'cache/*',
                'config/*',
                'content/*',
                'templates/*'
            ]
        ];
    }

    /**
     * Check for updates
     *
     * @return bool Whether updates are found or not
     */
    public function checkUpdates(): bool
    {
        if (time() - $this->registry->get('last-check') < $this->options['time']) {
            return $this->registry->get('up-to-date');
        }

        $this->loadRelease();

        $this->registry->set('release', $this->release);

        $this->registry->set('last-check', time());

        if (version_compare(Formwork::VERSION, $this->release['tag']) >= 0) {
            $this->registry->set('up-to-date', true);
            $this->registry->save();
            return true;
        }

        if (isset($this->getHeaders()['ETag'])) {
            $ETag = trim($this->headers['ETag'], '"');

            if ($this->registry->get('etag') === $ETag) {
                $this->registry->set('up-to-date', true);
                $this->registry->save();
                return true;
            }
        }

        $this->registry->set('up-to-date', false);
        $this->registry->save();
        return false;
    }

    /**
     * Update Formwork
     *
     * @return bool|null Whether Formwork was updated or not
     */
    public function update(): ?bool
    {
        $this->checkUpdates();

        if (!$this->options['force'] && $this->registry->get('up-to-date')) {
            return null;
        }

        $this->loadRelease();

        FileSystem::download($this->release['archive'], $this->options['tempFile'], true, $this->context);

        if (!FileSystem::exists($this->options['tempFile'])) {
            throw new RuntimeException('Cannot update Formwork, archive not downloaded');
        }

        $zip = new ZipArchive();
        $zip->open($this->options['tempFile']);
        $baseFolder = $zip->getNameIndex(0);
        $installedFiles = [];

        for ($i = 1; $i < $zip->numFiles; $i++) {
            $source = Str::removeStart($zip->getNameIndex($i), $baseFolder);
            $destination = ROOT_PATH . $source;
            $destinationDirectory = dirname($destination);

            if ($this->isCopiable($source)) {
                if (!FileSystem::exists($destinationDirectory)) {
                    FileSystem::createDirectory($destinationDirectory);
                }
                if (!Str::endsWith($destination, DS)) {
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

        $this->registry->set('last-update', time());

        if (isset($this->getHeaders()['ETag'])) {
            $ETag = trim($this->headers['ETag'], '"');
            $this->registry->set('etag', $ETag);
        }

        $this->registry->set('up-to-date', true);
        $this->registry->save();

        return true;
    }

    /**
     * Get latest release data
     */
    public function latestRelease(): array
    {
        return $this->registry->get('release');
    }

    /**
     * Load latest release data
     */
    protected function loadRelease(): void
    {
        if ($this->release !== null) {
            return;
        }

        $data = json_decode(FileSystem::fetch(self::API_RELEASE_URI, $this->context), true);

        if (!$data) {
            throw new RuntimeException('Cannot fetch latest Formwork release data');
        }

        $this->release = [
            'name'    => $data['name'],
            'tag'     => $data['tag_name'],
            'date'    => strtotime($data['published_at']),
            'archive' => $data['zipball_url']
        ];

        if ($this->options['preferDistAssets'] && !empty($data['assets'])) {
            $assetName = 'formwork-' . $data['tag_name'] . '.zip';
            $key = array_search($assetName, array_column($data['assets'], 'name'), true);

            if ($key !== false) {
                $this->release['archive'] = $data['assets'][$key]['browser_download_url'];
            }
        }
    }

    /**
     * Get release archive headers
     */
    protected function getHeaders(): array
    {
        if ($this->headers !== null) {
            return $this->headers;
        }
        return $this->headers = get_headers($this->release['archive'], 1, $this->context);
    }

    /**
     * Return whether a file is copiable or not
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
     * Return deletable files based on installed ones
     */
    protected function findDeletableFiles(array $installedFiles): array
    {
        $list = [];
        foreach ($installedFiles as $path) {
            $list[] = $path;
            if (FileSystem::isDirectory($path, false)) {
                foreach (FileSystem::listContents($path, FileSystem::LIST_ALL) as $item) {
                    $item = FileSystem::joinPaths($path, $item);
                    if (FileSystem::isDirectory($item) && !FileSystem::isEmptyDirectory($item)) {
                        continue;
                    }
                    $list[] = $item;
                }
            }
        }
        return array_diff($list, $installedFiles);
    }

    /**
     * Initialize registry data
     */
    protected function initializeRegistry(): void
    {
        foreach ($this->registryDefaults as $key => $value) {
            $this->registry->set($key, $value);
        }
        $this->registry->save();
    }
}
