<?php

namespace Formwork\Updater;

use DateTimeImmutable;
use Formwork\Cms\App;
use Formwork\Http\Client;
use Formwork\Log\Registry;
use Formwork\Parsers\Json;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use RuntimeException;
use ZipArchive;

final class Updater
{
    /**
     * GitHub repository from which updates are retrieved
     */
    private const string REPOSITORY = 'getformwork/formwork';

    /**
     * GitHub API latest release URI
     */
    private const string API_RELEASE_URI = 'https://api.github.com/repos/' . self::REPOSITORY . '/releases/latest';

    /**
     * Updates registry
     */
    private Registry $registry;

    /**
     * Updates registry default data
     *
     * @var array{lastCheck: ?int, lastUpdate: ?int, currentRelease: string, releaseArchiveEtag: ?string, release: ?array{name: string, tag: string, date: int, archive: string}, upToDate: bool}
     */
    private array $registryDefaults = [
        'lastCheck'          => null,
        'lastUpdate'         => null,
        'currentRelease'     => App::VERSION,
        'releaseArchiveEtag' => null,
        'release'            => null,
        'upToDate'           => false,
    ];

    /**
     * HTTP Client to make requests
     */
    private Client $client;

    /**
     * Array containing release information
     *
     * @var array{name: string, tag: string, date: int, archive: string}
     */
    private array $release;

    /**
     * Release archive headers
     *
     * @var array<string, string>
     */
    private array $releaseArchiveHeaders;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private array $options,
        private App $app,
    ) {
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
        if (
            !$this->options['force']
            && $this->registry->has('currentRelease') && $this->registry->get('currentRelease') === App::VERSION
            && $this->registry->has('lastCheck') && time() - $this->registry->get('lastCheck') < $this->options['time']
        ) {
            $this->release = $this->registry->get('release');
            return $this->registry->get('upToDate');
        }

        $this->loadRelease();

        $this->registry->set('lastCheck', time());
        $this->registry->set('currentRelease', App::VERSION);
        $this->registry->set('release', $this->release);

        $isInstallable = $this->isVersionInstallable($this->release['tag']);
        $isSameVersion = $this->release['tag'] === $this->registry->get('currentRelease');

        // Only fetch remote headers when we already know it's the same version
        $etagUnchanged = $isSameVersion
            && (
                // Don't consider ETag if we don't have it stored (fresh install or registry reset)
                !$this->registry->has('releaseArchiveEtag')

                || $this->registry->get('releaseArchiveEtag') === $this->getReleaseArchiveEtag()
            );

        if (!$isInstallable || $etagUnchanged) {
            $this->registry->set('upToDate', true);
            $this->registry->save();
            return true;
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

        if ($this->registry->get('upToDate')) {
            return null;
        }

        $this->client->download($this->release['archive'], $this->options['tempFile']);

        if (!FileSystem::exists($this->options['tempFile'])) {
            throw new RuntimeException('Cannot update Formwork, archive not downloaded');
        }

        $zipArchive = new ZipArchive();
        $zipArchive->open($this->options['tempFile'], ZipArchive::RDONLY);
        $installedFiles = [];
        $counter = count($zipArchive);

        for ($i = 0; $i < $counter; $i++) {
            $filename = $zipArchive->getNameIndex($i);

            if ($filename === false) {
                throw new RuntimeException('Cannot get filename from zip archive');
            }

            $root = ROOT_PATH;
            $destination = FileSystem::joinPaths($root, $filename);
            $destinationDirectory = dirname($destination);

            if ($this->isCopiable($filename)) {
                if (!FileSystem::exists($destinationDirectory)) {
                    FileSystem::createDirectory($destinationDirectory);
                }
                if (!Str::endsWith($destination, DIRECTORY_SEPARATOR)) {
                    if ($zipArchive->extractTo($root, $filename) === false) {
                        throw new RuntimeException(sprintf('Cannot extract "%s" from zip archive', $filename));
                    }
                    if ($zipArchive->getExternalAttributesIndex($i, $opsys, $perms) && $opsys === ZipArchive::OPSYS_UNIX) {
                        @chmod($destination, ($perms >> 16) & 0o777);
                    }
                }
                $installedFiles[] = $destination;
            }
        }

        $zipArchive->close();

        FileSystem::delete($this->options['tempFile']);

        if ($this->options['cleanupAfterInstall']) {
            $deletableFiles = $this->findDeletableFiles($installedFiles);
            foreach ($deletableFiles as $deletableFile) {
                FileSystem::delete($deletableFile);
            }
        }

        $this->registry->set('lastUpdate', time());
        $this->registry->set('currentRelease', $this->release['tag']);
        $this->registry->set('releaseArchiveEtag', $this->getReleaseArchiveEtag());

        $this->registry->set('upToDate', true);
        $this->registry->save();

        return true;
    }

    /**
     * Get latest release data
     *
     * @return ?array{name: string, tag: string, date: int, archive: string}
     */
    public function latestRelease(): ?array
    {
        return $this->registry->get('release');
    }

    /**
     * Load latest release data
     */
    private function loadRelease(): void
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
            $assetName = "formwork-{$data['tag_name']}.zip";
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
    private function getReleaseArchiveHeaders(): array
    {
        return $this->releaseArchiveHeaders ?? ($this->releaseArchiveHeaders = $this->client->fetchHeaders($this->release['archive'])->toArray());
    }

    /**
     * Get release archive ETag
     */
    private function getReleaseArchiveEtag(): string
    {
        return trim($this->getReleaseArchiveHeaders()['Etag'], '"');
    }

    /**
     * Return whether a version is installable based on the current version of Formwork
     */
    private function isVersionInstallable(string $version): bool
    {
        $semVer = SemVer::fromString($this->app::VERSION);
        $new = SemVer::fromString($version);
        return !$new->isPrerelease() && $semVer->compareWith($new, '!=') && $semVer->compareWith($new, '^');
    }

    /**
     * Return whether a file is copiable or not
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
     * Return deletable files based on installed ones
     *
     * @param list<string> $installedFiles
     *
     * @return array<string>
     */
    private function findDeletableFiles(array $installedFiles): array
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
    private function initializeRegistry(): void
    {
        foreach ($this->registryDefaults as $key => $value) {
            $this->registry->set($key, $value);
        }
        $this->registry->save();
    }
}
