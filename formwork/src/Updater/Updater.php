<?php

namespace Formwork\Updater;

use DateTimeImmutable;
use Formwork\App;
use Formwork\Config\Config;
use Formwork\Http\Client;
use Formwork\Log\Registry;
use Formwork\Parsers\Json;
use Formwork\Utils\FileSystem;
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
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * Updates registry
     */
    protected Registry $registry;

    /**
     * Updates registry default data
     *
     * @var array{lastCheck: ?int, lastUpdate: ?int, etag: ?string, release: ?array{name: string, tag: string, date: int, archive: string}, upToDate: bool}
     */
    protected array $registryDefaults = [
        'lastCheck'  => null,
        'lastUpdate' => null,
        'etag'       => null,
        'release'    => null,
        'upToDate'   => false,
    ];

    /**
     * HTTP Client to make requests
     */
    protected Client $client;

    /**
     * Array containing release information
     *
     * @var array{name: string, tag: string, date: int, archive: string}
     */
    protected array $release;

    /**
     * Headers to send in HTTP(S) requests
     *
     * @var array<string, string>
     */
    protected array $headers;

    /**
     * Whether Formwork is up-to-date
     */
    protected bool $upToDate;

    /**
     * Create a new Updater instance
     */
    public function __construct(protected App $app, protected Config $config)
    {
        $this->options = $config->get('system.updates');

        $this->registry = new Registry($this->options['registryFile']);

        if ($this->registry->toArray() === []) {
            $this->initializeRegistry();
        }

        $this->client = new Client(['headers' => ['Accept' => 'application/vnd.github.v3+json']]);
    }

    /**
     * Check for updates
     *
     * @return bool Whether updates are found or not
     */
    public function checkUpdates(): bool
    {
        if (time() - $this->registry->get('lastCheck') < $this->options['time']) {
            return $this->registry->get('upToDate');
        }

        $this->loadRelease();

        $this->registry->set('release', $this->release);

        $this->registry->set('lastCheck', time());

        if (!$this->isVersionInstallable($this->release['tag'])) {
            $this->registry->set('upToDate', true);
            $this->registry->save();
            return true;
        }

        if (isset($this->getHeaders()['Etag'])) {
            $ETag = trim($this->headers['Etag'], '"');

            if ($this->registry->get('etag') === $ETag) {
                $this->registry->set('upToDate', true);
                $this->registry->save();
                return true;
            }
        }

        $this->registry->set('upToDate', false);
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

        if (!$this->options['force'] && $this->registry->get('upToDate')) {
            return null;
        }

        $this->loadRelease();

        $this->client->download($this->release['archive'], $this->options['tempFile']);

        if (!FileSystem::exists($this->options['tempFile'])) {
            throw new RuntimeException('Cannot update Formwork, archive not downloaded');
        }

        $zipArchive = new ZipArchive();
        $zipArchive->open($this->options['tempFile']);
        $baseFolder = $zipArchive->getNameIndex(0);

        if ($baseFolder === false) {
            throw new RuntimeException('Cannot get base folder from zip archive');
        }

        $installedFiles = [];

        for ($i = 1; $i < $zipArchive->numFiles; $i++) {
            $filename = $zipArchive->getNameIndex($i);

            if ($filename === false) {
                throw new RuntimeException('Cannot get filename from zip archive');
            }

            $source = Str::removeStart($filename, $baseFolder);
            $destination = ROOT_PATH . '/' . $source;
            $destinationDirectory = dirname($destination);

            if ($this->isCopiable($source)) {
                if (!FileSystem::exists($destinationDirectory)) {
                    FileSystem::createDirectory($destinationDirectory);
                }
                if (!Str::endsWith($destination, DS)) {
                    $contents = $zipArchive->getFromIndex($i);
                    if ($contents === false) {
                        throw new RuntimeException(sprintf('Cannot read "%s" from zip archive', $filename));
                    }
                    FileSystem::write($destination, $contents);
                }
                $installedFiles[] = $destination;
            }
        }

        FileSystem::delete($this->options['tempFile']);

        if ($this->options['cleanupAfterInstall']) {
            $deletableFiles = $this->findDeletableFiles($installedFiles);
            foreach ($deletableFiles as $deletableFile) {
                FileSystem::delete($deletableFile);
            }
        }

        $this->registry->set('lastUpdate', time());

        if (isset($this->getHeaders()['Etag'])) {
            $ETag = trim($this->headers['Etag'], '"');
            $this->registry->set('etag', $ETag);
        }

        $this->registry->set('upToDate', true);
        $this->registry->save();

        return true;
    }

    /**
     * Get latest release data
     *
     * @return array{name: string, tag: string, date: int, archive: string}
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

        $data = Json::parse($this->client->fetch(self::API_RELEASE_URI)->content());

        if ($data === []) {
            throw new RuntimeException('Cannot fetch latest Formwork release data');
        }

        $releaseDate = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:sO', $data['published_at']);

        if ($releaseDate === false) {
            throw new RuntimeException('Cannot parse release date');
        }

        $this->release = [
            'name'    => $data['name'],
            'tag'     => $data['tag_name'],
            'date'    => $releaseDate->getTimestamp(),
            'archive' => $data['zipball_url'],
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
     *
     * @return array<string, string>
     */
    protected function getHeaders(): array
    {
        return $this->headers ?? ($this->headers = $this->client->fetchHeaders($this->release['archive']));
    }

    /**
     * Return whether a version is installable based on the current version of Formwork
     */
    protected function isVersionInstallable(string $version): bool
    {
        $semVer = SemVer::fromString($this->app::VERSION);
        $new = SemVer::fromString($version);
        return !$new->isPrerelease() && $semVer->compareWith($new, '!=') && $semVer->compareWith($new, '^');
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
     *
     * @param list<string> $installedFiles
     *
     * @return list<string>
     */
    protected function findDeletableFiles(array $installedFiles): array
    {
        $list = [];
        foreach ($installedFiles as $installedFile) {
            $list[] = $installedFile;
            if (FileSystem::isDirectory($installedFile, assertExists: false)) {
                foreach (FileSystem::listContents($installedFile, FileSystem::LIST_ALL) as $item) {
                    $item = FileSystem::joinPaths($installedFile, $item);
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
