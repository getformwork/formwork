<?php

namespace Formwork\Updater;

use DateTimeImmutable;
use Formwork\Backup\Utils\ZipErrors;
use Formwork\Cms\App;
use Formwork\Http\Client;
use Formwork\Log\Registry;
use Formwork\Parsers\Json;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;
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
     * @var array{lastCheck: ?int, lastUpdate: ?int, currentRelease: string, releaseArchiveEtag: ?string, release: ?array{name: string, tag: string, date: int, archive: string, checksum: ?string}, upToDate: bool, preferDistAssets: bool}
     */
    private array $registryDefaults = [
        'lastCheck'          => null,
        'lastUpdate'         => null,
        'currentRelease'     => App::VERSION,
        'releaseArchiveEtag' => null,
        'release'            => null,
        'upToDate'           => false,
        'preferDistAssets'   => true,
    ];

    /**
     * HTTP Client to make requests
     */
    private Client $client;

    /**
     * Array containing release information
     *
     * @var array{name: string, tag: string, date: int, archive: string, checksum: ?string}
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
    ) {
        if (!extension_loaded('zip')) {
            throw new RuntimeException(sprintf('Class %s requires the extension "zip" to be enabled', self::class));
        }

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
    public function checkUpdates(?bool $force = null, ?bool $preferDistAssets = null): bool
    {
        $preferDistAssets ??= $this->options['preferDistAssets'];

        if (
            !($force ?? $this->options['force'])
            && $this->registry->has('currentRelease') && $this->registry->get('currentRelease') === App::VERSION
            && $this->registry->has('lastCheck') && time() - $this->registry->get('lastCheck') < $this->options['time']
            && $this->registry->has('preferDistAssets') && $this->registry->get('preferDistAssets') === $preferDistAssets
        ) {
            $this->release = $this->registry->get('release');
            return $this->registry->get('upToDate');
        }

        $this->loadRelease($preferDistAssets);

        $this->registry->set('lastCheck', time());
        $this->registry->set('currentRelease', App::VERSION);
        $this->registry->set('release', $this->release);
        $this->registry->set('preferDistAssets', $preferDistAssets);

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
    public function update(?bool $force = null, ?bool $preferDistAssets = null, ?bool $cleanupAfterInstall = null): ?bool
    {
        $cleanupAfterInstall ??= $this->options['cleanupAfterInstall'];

        $this->checkUpdates($force, $preferDistAssets);

        if ($this->registry->get('upToDate')) {
            return null;
        }

        $this->acquireLock();

        try {
            $installedFiles = $this->downloadAndExtractRelease();

            if ($cleanupAfterInstall) {
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
        } finally {
            $this->releaseLock();
        }

        return true;
    }

    /**
     * Get latest release data
     *
     * @return ?array{name: string, tag: string, date: int, archive: string, checksum: ?string}
     */
    public function latestRelease(): ?array
    {
        return $this->registry->get('release');
    }

    /**
     * Download the release archive and extract it into ROOT_PATH, always cleaning up the
     * temporary archive file, even if the download or the extraction fails
     *
     * @return list<string>
     */
    private function downloadAndExtractRelease(): array
    {
        try {
            $this->client->download($this->release['archive'], $this->options['tempFile']);

            if (!FileSystem::exists($this->options['tempFile'])) {
                throw new RuntimeException('Cannot download update archive');
            }

            $this->verifyArchiveChecksum($this->options['tempFile']);

            return $this->extractRelease($this->options['tempFile']);
        } finally {
            if (FileSystem::exists($this->options['tempFile'])) {
                FileSystem::delete($this->options['tempFile']);
            }
        }
    }

    /**
     * Verify the downloaded archive matches the sha256 checksum published for the release
     * asset, if any is available. The auto-generated GitHub source archive has no checksum
     */
    private function verifyArchiveChecksum(string $archiveFile): void
    {
        if ($this->release['checksum'] === null) {
            return;
        }

        $hash = hash_file('sha256', $archiveFile);

        if ($hash === false || !hash_equals($this->release['checksum'], $hash)) {
            throw new RuntimeException('Downloaded archive does not match the expected checksum');
        }
    }

    /**
     * Extract a release archive into ROOT_PATH and return the list of installed files
     *
     * @return list<string>
     */
    private function extractRelease(string $archiveFile): array
    {
        $zipArchive = new ZipArchive();

        $status = $zipArchive->open($archiveFile, ZipArchive::RDONLY);

        if ($status !== true) {
            /** @var key-of<ZipErrors::ERROR_MESSAGES> $status */
            throw new RuntimeException(sprintf('Cannot open update archive: %s', ZipErrors::ERROR_MESSAGES[$status]));
        }

        try {
            $installedFiles = [];
            $counter = count($zipArchive);

            for ($i = 0; $i < $counter; $i++) {
                $filename = $zipArchive->getNameIndex($i);

                if ($filename === false) {
                    throw new RuntimeException('Cannot get filename from zip archive');
                }

                $root = ROOT_PATH;
                $destination = Path::resolve($filename, $root, DIRECTORY_SEPARATOR);

                if (!Path::isRelativeTo($destination, $root)) {
                    throw new RuntimeException(sprintf('Cannot extract "%s" from zip archive: invalid destination', $filename));
                }

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

            return $installedFiles;
        } finally {
            $zipArchive->close();
        }
    }

    /**
     * Acquire an exclusive lock preventing concurrent update runs. A stale lock
     * (older than the configured timeout) is considered abandoned and reused
     */
    private function acquireLock(): void
    {
        $lockFile = $this->getLockFile();

        if (FileSystem::exists($lockFile) && (time() - FileSystem::lastModifiedTime($lockFile)) < $this->options['lockTimeout']) {
            throw new RuntimeException('An update is already in progress');
        }

        FileSystem::write($lockFile, (string) time());
    }

    /**
     * Release the update lock
     */
    private function releaseLock(): void
    {
        if (FileSystem::exists($lockFile = $this->getLockFile())) {
            FileSystem::delete($lockFile);
        }
    }

    /**
     * Get the update lock file path
     */
    private function getLockFile(): string
    {
        return $this->options['tempFile'] . '.lock';
    }

    /**
     * Load latest release data
     */
    private function loadRelease(bool $preferDistAssets = true): void
    {
        $data = Json::parse($this->client->fetch(self::API_RELEASE_URI)->content());

        if ($data === []) {
            throw new RuntimeException('Cannot fetch latest Formwork release data');
        }

        $releaseDate = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:sO', $data['published_at']);

        if ($releaseDate === false) {
            throw new RuntimeException('Cannot parse release date');
        }

        $this->release = [
            'name'     => $data['name'],
            'tag'      => $data['tag_name'],
            'date'     => $releaseDate->getTimestamp(),
            'archive'  => $data['zipball_url'],
            'checksum' => null,
        ];

        if ($preferDistAssets && !empty($data['assets'])) {
            $assetName = "formwork-{$data['tag_name']}.zip";
            $key = array_search($assetName, array_column($data['assets'], 'name'), true);

            if ($key !== false) {
                $this->release['archive'] = $data['assets'][$key]['browser_download_url'];
                $this->release['checksum'] = $this->parseSha256Checksum($data['assets'][$key]['digest'] ?? null);
            }
        }
    }

    /**
     * Parse a GitHub asset "checksum" field (e.g. "sha256:abc123...") into its hex checksum
     */
    private function parseSha256Checksum(?string $checksum): ?string
    {
        if ($checksum === null || !Str::startsWith($checksum, 'sha256:')) {
            return null;
        }

        return Str::removeStart($checksum, 'sha256:');
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
        $semVer = SemVer::fromString($this->registry->get('currentRelease'));
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
