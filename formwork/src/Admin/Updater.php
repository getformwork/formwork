<?php

namespace Formwork\Admin;

use Formwork\Formwork;
use Formwork\Parsers\JSON;
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPClient;
use Formwork\Utils\Registry;
use Formwork\Utils\SemVer;
use Formwork\Utils\Str;
use RuntimeException;
use ZipArchive;

class Updater
{
    /**
     * GitHub repository from which updates are retrieved
     */
    protected const REPOSITORY = 'getformwork/formwork';

    /**
     * GitHub API latest release URI
     */
    protected const API_RELEASE_URI = 'https://api.github.com/repos/' . self::REPOSITORY . '/releases/latest';

    /**
     * Updater options
     */
    protected array $options = [];

    /**
     * Updates registry
     */
    protected Registry $registry;

    /**
     * Updates registry default data
     */
    protected array $registryDefaults = [
        'last-check'  => null,
        'last-update' => null,
        'etag'        => null,
        'release'     => null,
        'up-to-date'  => false
    ];

    /**
     * HTTP Client to make requests
     */
    protected HTTPClient $client;

    /**
     * Array containing release information
     */
    protected array $release;

    /**
     * Headers to send in HTTP(S) requests
     */
    protected array $headers;

    /**
     * Whether Formwork is up-to-date
     */
    protected bool $upToDate;

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

        $this->client = new HTTPClient(['headers' => ['Accept' => 'application/vnd.github.v3+json']]);
    }

    /**
     * Return updater default options
     */
    public function defaults(): array
    {
        return [
            'time'                => 900,
            'force'               => false,
            'registryFile'        => Formwork::instance()->config()->get('admin.paths.logs') . 'updates.json',
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
                'site/*'
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

        if (!$this->isVersionInstallable($this->release['tag'])) {
            $this->registry->set('up-to-date', true);
            $this->registry->save();
            return true;
        }

        if (isset($this->getHeaders()['Etag'])) {
            $ETag = trim($this->headers['Etag'], '"');

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

        $this->client->download($this->release['archive'], $this->options['tempFile']);

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
            foreach ($deletableFiles as $file) {
                FileSystem::delete($file);
            }
        }

        $this->registry->set('last-update', time());

        if (isset($this->getHeaders()['Etag'])) {
            $ETag = trim($this->headers['Etag'], '"');
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
        if (isset($this->release)) {
            return;
        }

        $data = JSON::parse($this->client->fetch(self::API_RELEASE_URI)->content());

        if (!$data) {
            throw new RuntimeException('Cannot fetch latest Formwork release data');
        }

        $this->release = [
            'name'    => $data['name'],
            'tag'     => $data['tag_name'],
            'date'    => Date::toTimestamp($data['published_at'], DATE_ISO8601),
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
        if (isset($this->headers)) {
            return $this->headers;
        }
        return $this->headers = $this->client->fetchHeaders($this->release['archive']);
    }

    /**
     * Return whether a version is installable based on the current version of Formwork
     */
    protected function isVersionInstallable(string $version): bool
    {
        $current = SemVer::fromString(Formwork::VERSION);
        $new = SemVer::fromString($version);
        return !$new->isPrerelease() && $current->compareWith($new, '!=') && $current->compareWith($new, '^');
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
