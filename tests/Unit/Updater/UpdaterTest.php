<?php

namespace Formwork\Tests\Unit\Updater;

use Formwork\Cms\App;
use Formwork\Http\Client;
use Formwork\Http\Response;
use Formwork\Http\ResponseHeaders;
use Formwork\Parsers\Json;
use Formwork\Tests\TestCase;
use Formwork\Updater\SemVer;
use Formwork\Updater\Updater;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use ZipArchive;

/**
 * The extraction step of the updater writes into ROOT_PATH, so these tests never let an update run to completion:
 * every scenario either stops before extraction or only feeds the extractor archives that must be refused
 */
#[CoversClass(Updater::class)]
final class UpdaterTest extends TestCase
{
    private string $registryFile;

    private string $tempFile;

    /**
     * @var list<string>
     */
    private array $markers = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        FileSystem::createDirectory(TESTS_TMP_PATH . '/updater');
        $this->registryFile = TESTS_TMP_PATH . '/updater/updates.json';
        $this->tempFile = TESTS_TMP_PATH . '/updater/update.zip';
    }

    protected function tearDown(): void
    {
        foreach ($this->markers as $marker) {
            if (file_exists($marker)) {
                unlink($marker);
            }
        }
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    // Registry

    public function testRegistryIsInitializedWithDefaults(): void
    {
        $this->updater();

        $registry = Json::parseFile($this->registryFile);

        $this->assertSame(App::VERSION, $registry['currentRelease']);
        $this->assertNull($registry['lastCheck']);
        $this->assertNull($registry['lastUpdate']);
        $this->assertFalse($registry['upToDate']);
        $this->assertNull($registry['release']);
    }

    public function testExistingRegistryIsNotOverwritten(): void
    {
        FileSystem::write($this->registryFile, Json::encode(['currentRelease' => '0.0.1', 'custom' => 'kept']));

        $this->updater();

        $this->assertSame('kept', Json::parseFile($this->registryFile)['custom']);
    }

    public function testLatestReleaseIsNullBeforeAnyCheck(): void
    {
        $this->assertNull($this->updater()->latestRelease());
    }

    // Checking for updates

    public function testNewerMinorReleaseMeansUpdatesAreAvailable(): void
    {
        $updater = $this->updater(client: $this->clientWithRelease($this->tag(minor: 1)));

        $this->assertFalse($updater->checkUpdates(force: true), 'checkUpdates() returns whether the installation is up to date');
        $release = $updater->latestRelease();
        $this->assertNotNull($release);
        $this->assertSame($this->tag(minor: 1), $release['tag']);
        $this->assertSame('Release name', $release['name']);
        $this->assertSame(1_714_557_600, $release['date']);
        $this->assertSame('https://api.example.test/zipball', $release['archive']);
        $this->assertNull($release['checksum']);
    }

    public function testCurrentVersionIsUpToDate(): void
    {
        $updater = $this->updater(client: $this->clientWithRelease(App::VERSION));

        $this->assertTrue($updater->checkUpdates(force: true));
    }

    public function testOlderReleasesAreNotInstalled(): void
    {
        $version = SemVer::fromString(App::VERSION);
        $older = $version->patch() > 0
            ? sprintf('%d.%d.%d', $version->major(), $version->minor(), $version->patch() - 1)
            : sprintf('%d.%d.%d', $version->major(), max($version->minor() - 1, 0), 0);

        $this->assertTrue($this->updater(client: $this->clientWithRelease($older))->checkUpdates(force: true));
    }

    public function testMajorReleasesAreNotOfferedAsUpdates(): void
    {
        $this->assertTrue($this->updater(client: $this->clientWithRelease($this->tag(major: 1)))->checkUpdates(force: true));
    }

    public function testPrereleasesAreNotOfferedAsUpdates(): void
    {
        $this->assertTrue($this->updater(client: $this->clientWithRelease($this->tag(minor: 1) . '-beta.1'))->checkUpdates(force: true));
    }

    public function testResultIsCachedForTheConfiguredTime(): void
    {
        $client = $this->clientWithRelease($this->tag(minor: 1), expectedFetches: 1);
        $updater = $this->updater(['time' => 900], $client);

        $first = $updater->checkUpdates(force: true);
        $second = $updater->checkUpdates();

        $this->assertSame($first, $second);
        $this->assertNotNull($updater->latestRelease());
    }

    public function testForceBypassesTheCache(): void
    {
        $client = $this->clientWithRelease($this->tag(minor: 1), expectedFetches: 2);
        $updater = $this->updater(['time' => 900], $client);

        $updater->checkUpdates(force: true);
        $updater->checkUpdates(force: true);
    }

    public function testExpiredCacheIsRefreshed(): void
    {
        $client = $this->clientWithRelease($this->tag(minor: 1), expectedFetches: 2);
        $updater = $this->updater(['time' => 0], $client);

        $updater->checkUpdates(force: true);
        $updater->checkUpdates();
    }

    public function testChangingTheAssetPreferenceInvalidatesTheCache(): void
    {
        $client = $this->clientWithRelease($this->tag(minor: 1), expectedFetches: 2);
        $updater = $this->updater(['time' => 900], $client);

        $updater->checkUpdates(force: true, preferDistAssets: true);
        $updater->checkUpdates(preferDistAssets: false);
    }

    public function testDistributionAssetIsPreferredWhenAvailable(): void
    {
        $tag = $this->tag(minor: 1);
        $client = $this->clientWithRelease($tag, assets: [
            ['name' => 'other.zip', 'browser_download_url' => 'https://assets.example.test/other.zip'],
            ['name' => "formwork-{$tag}.zip", 'browser_download_url' => 'https://assets.example.test/dist.zip', 'digest' => 'sha256:' . str_repeat('ab', 32)],
        ]);
        $updater = $this->updater(client: $client);

        $updater->checkUpdates(force: true, preferDistAssets: true);

        $release = $updater->latestRelease();
        $this->assertNotNull($release);
        $this->assertSame('https://assets.example.test/dist.zip', $release['archive']);
        $this->assertSame(str_repeat('ab', 32), $release['checksum']);
    }

    public function testSourceArchiveIsUsedWhenDistributionAssetsAreNotPreferred(): void
    {
        $tag = $this->tag(minor: 1);
        $client = $this->clientWithRelease($tag, assets: [
            ['name' => "formwork-{$tag}.zip", 'browser_download_url' => 'https://assets.example.test/dist.zip', 'digest' => 'sha256:' . str_repeat('ab', 32)],
        ]);
        $updater = $this->updater(client: $client);

        $updater->checkUpdates(force: true, preferDistAssets: false);

        $release = $updater->latestRelease();
        $this->assertNotNull($release);
        $this->assertSame('https://api.example.test/zipball', $release['archive']);
        $this->assertNull($release['checksum']);
    }

    public function testSourceArchiveIsUsedWhenTheDistributionAssetIsMissing(): void
    {
        $client = $this->clientWithRelease($this->tag(minor: 1), assets: [
            ['name' => 'unrelated.zip', 'browser_download_url' => 'https://assets.example.test/unrelated.zip'],
        ]);
        $updater = $this->updater(client: $client);

        $updater->checkUpdates(force: true);

        $release = $updater->latestRelease();
        $this->assertNotNull($release);
        $this->assertSame('https://api.example.test/zipball', $release['archive']);
    }

    public function testChecksumsOfOtherAlgorithmsAreIgnored(): void
    {
        $tag = $this->tag(minor: 1);
        $client = $this->clientWithRelease($tag, assets: [
            ['name' => "formwork-{$tag}.zip", 'browser_download_url' => 'https://assets.example.test/dist.zip', 'digest' => 'sha1:' . str_repeat('ab', 20)],
        ]);
        $updater = $this->updater(client: $client);

        $updater->checkUpdates(force: true);

        $release = $updater->latestRelease();
        $this->assertNotNull($release);
        $this->assertNull($release['checksum']);
    }

    public function testEmptyApiResponsesAreReported(): void
    {
        $updater = $this->updater(client: $this->clientReturning('{}'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot fetch latest Formwork release data');

        $updater->checkUpdates(force: true);
    }

    public function testApiErrorsAreReportedAsRuntimeExceptions(): void
    {
        $updater = $this->updater(client: $this->clientReturning('{"message": "API rate limit exceeded", "documentation_url": "https://docs.github.com"}'));

        $this->expectException(RuntimeException::class);

        $updater->checkUpdates(force: true);
    }

    public function testInvalidReleaseDatesAreReported(): void
    {
        $updater = $this->updater(client: $this->clientWithRelease($this->tag(minor: 1), publishedAt: 'yesterday'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot parse release date');

        $updater->checkUpdates(force: true);
    }

    public function testFailedChecksDoNotLeaveAStaleCacheBehind(): void
    {
        $client = $this->createStub(Client::class);
        $client->method('fetch')->willReturnOnConsecutiveCalls(
            $this->response('{}'),
            $this->response($this->releaseJson($this->tag(minor: 1))),
        );
        $updater = $this->updater(client: $client);

        try {
            $updater->checkUpdates(force: true);
        } catch (RuntimeException) {
            // First attempt fails
        }

        $this->assertFalse($updater->checkUpdates(), 'The failed check must not be cached as a success');
    }

    public function testRereleasedArchivesOfTheCurrentVersionAreDetectedThroughTheEtag(): void
    {
        FileSystem::write($this->registryFile, Json::encode([
            'lastCheck'          => null,
            'lastUpdate'         => null,
            'currentRelease'     => App::VERSION,
            'releaseArchiveEtag' => 'old-etag',
            'release'            => null,
            'upToDate'           => false,
            'preferDistAssets'   => true,
        ]));
        $client = $this->clientWithRelease(App::VERSION);
        $client->method('fetchHeaders')->willReturn(new ResponseHeaders(['ETag' => '"new-etag"']));
        $updater = $this->updater(client: $client);

        $this->assertFalse($updater->checkUpdates(force: true), 'A changed archive for the installed version should be offered again');
    }

    // Updating

    public function testUpdateDoesNothingWhenAlreadyUpToDate(): void
    {
        $client = $this->clientWithRelease(App::VERSION, mock: true);
        $client->expects($this->never())->method('download');
        $updater = $this->updater(client: $client);

        $this->assertNull($updater->update(force: true));
    }

    public function testChecksumMismatchAbortsTheUpdateAndCleansUp(): void
    {
        $tag = $this->tag(minor: 1);
        $client = $this->clientWithRelease($tag, assets: [
            ['name' => "formwork-{$tag}.zip", 'browser_download_url' => 'https://assets.example.test/dist.zip', 'digest' => 'sha256:' . str_repeat('0', 64)],
        ]);
        $client->method('download')->willReturnCallback(function (string $uri, string $file): void {
            FileSystem::write($file, 'not the archive that was announced');
        });
        $updater = $this->updater(client: $client);

        try {
            $updater->update(force: true);
            $this->fail('A checksum mismatch must abort the update.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('checksum', $exception->getMessage());
        }

        $this->assertFileDoesNotExist($this->tempFile, 'The downloaded archive must be removed');
        $this->assertFileDoesNotExist($this->tempFile . '.lock', 'The lock must be released');
        $this->assertNull(Json::parseFile($this->registryFile)['lastUpdate']);
    }

    public function testMissingDownloadAbortsTheUpdateAndReleasesTheLock(): void
    {
        $client = $this->clientWithRelease($this->tag(minor: 1));
        $client->method('download')->willReturnCallback(static function (): void {});
        $updater = $this->updater(client: $client);

        try {
            $updater->update(force: true);
            $this->fail('A missing archive must abort the update.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Cannot download update archive', $exception->getMessage());
        }

        $this->assertFileDoesNotExist($this->tempFile . '.lock');
    }

    public function testDownloadFailuresReleaseTheLock(): void
    {
        $client = $this->clientWithRelease($this->tag(minor: 1));
        $client->method('download')->willThrowException(new RuntimeException('network down'));
        $updater = $this->updater(client: $client);

        try {
            $updater->update(force: true);
            $this->fail('The download failure should propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('network down', $exception->getMessage());
        }

        $this->assertFileDoesNotExist($this->tempFile . '.lock');
    }

    public function testConcurrentUpdatesAreRefused(): void
    {
        FileSystem::write($this->tempFile . '.lock', (string) time());
        $client = $this->clientWithRelease($this->tag(minor: 1), mock: true);
        $client->expects($this->never())->method('download');
        $updater = $this->updater(['lockTimeout' => 300], $client);

        try {
            $updater->update(force: true);
            $this->fail('A fresh lock must block concurrent updates.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('already in progress', $exception->getMessage());
        }

        $this->assertFileExists($this->tempFile . '.lock', 'The lock of the running update must be left alone');
    }

    public function testStaleLocksAreReplaced(): void
    {
        FileSystem::write($this->tempFile . '.lock', '1');
        touch($this->tempFile . '.lock', time() - 3600);
        $client = $this->clientWithRelease($this->tag(minor: 1), mock: true);
        $client->expects($this->once())->method('download')->willThrowException(new RuntimeException('stop here'));
        $updater = $this->updater(['lockTimeout' => 300], $client);

        $this->expectExceptionMessage('stop here');

        $updater->update(force: true);
    }

    // Extraction (only archives that must be refused)

    /**
     * @return iterable<string, array{string}>
     */
    public static function escapingEntries(): iterable
    {
        yield 'parent directory' => ['../formwork-zipslip-parent.txt'];
        yield 'several parents' => ['../../../../../../../../tmp/formwork-zipslip-deep.txt'];
        yield 'nested then parent' => ['formwork/../../formwork-zipslip-nested.txt'];
        yield 'absolute path' => ['/tmp/formwork-zipslip-absolute.txt'];
        yield 'sibling sharing the root prefix' => ['../formwork-sibling/zipslip.txt'];
    }

    #[DataProvider('escapingEntries')]
    public function testArchivesEscapingTheInstallationRootAreRefused(string $entry): void
    {
        $archive = TESTS_TMP_PATH . '/updater/malicious.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($archive, ZipArchive::CREATE));
        $zip->addFromString($entry, 'malicious payload');
        $zip->close();

        foreach ([dirname(ROOT_PATH) . '/formwork-zipslip-parent.txt', '/tmp/formwork-zipslip-deep.txt', dirname(ROOT_PATH) . '/formwork-zipslip-nested.txt', '/tmp/formwork-zipslip-absolute.txt', dirname(ROOT_PATH) . '/formwork-sibling/zipslip.txt'] as $marker) {
            $this->markers[] = $marker;
            $this->assertFileDoesNotExist($marker);
        }

        try {
            $this->call($this->updater(), 'extractRelease', $archive);
            $this->fail(sprintf('The entry "%s" must be refused.', $entry));
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('invalid destination', $exception->getMessage());
        }

        foreach ($this->markers as $marker) {
            $this->assertFileDoesNotExist($marker, 'Nothing may be written outside of the installation root');
        }
    }

    public function testArchivesThatAreNotZipFilesAreReported(): void
    {
        FileSystem::write(TESTS_TMP_PATH . '/updater/fake.zip', 'definitely not a zip');

        try {
            $this->call($this->updater(), 'extractRelease', TESTS_TMP_PATH . '/updater/fake.zip');
            $this->fail('Invalid archives must be refused.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Cannot open update archive', $exception->getMessage());
        }
    }

    // Helpers exercised through reflection

    public function testChecksumVerificationAcceptsTheMatchingHash(): void
    {
        FileSystem::write($this->tempFile, 'archive');
        $updater = $this->updaterWithRelease(['checksum' => hash('sha256', 'archive')]);

        $this->call($updater, 'verifyArchiveChecksum', $this->tempFile);

        $this->addToAssertionCount(1);
    }

    public function testChecksumVerificationRejectsADifferentHash(): void
    {
        FileSystem::write($this->tempFile, 'archive');
        $updater = $this->updaterWithRelease(['checksum' => hash('sha256', 'other')]);

        $this->expectException(RuntimeException::class);

        $this->call($updater, 'verifyArchiveChecksum', $this->tempFile);
    }

    public function testChecksumVerificationIsSkippedWithoutAPublishedChecksum(): void
    {
        FileSystem::write($this->tempFile, 'archive');
        $updater = $this->updaterWithRelease(['checksum' => null]);

        $this->call($updater, 'verifyArchiveChecksum', $this->tempFile);

        $this->addToAssertionCount(1);
    }

    public function testChecksumVerificationRejectsEmptyChecksums(): void
    {
        FileSystem::write($this->tempFile, 'archive');
        $updater = $this->updaterWithRelease(['checksum' => '']);

        $this->expectException(RuntimeException::class);

        $this->call($updater, 'verifyArchiveChecksum', $this->tempFile);
    }

    /**
     * @return iterable<string, array{?string, ?string}>
     */
    public static function digests(): iterable
    {
        yield 'sha256' => ['sha256:abcdef', 'abcdef'];
        yield 'sha1' => ['sha1:abcdef', null];
        yield 'null' => [null, null];
        yield 'empty' => ['', null];
        yield 'no algorithm' => ['abcdef', null];
        yield 'uppercase algorithm' => ['SHA256:abcdef', null];
    }

    #[DataProvider('digests')]
    public function testDigestParsing(?string $digest, ?string $expected): void
    {
        $this->assertSame($expected, $this->call($this->updater(), 'parseSha256Checksum', $digest));
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function installability(): iterable
    {
        yield 'next patch' => ['2.3.12', '2.3.13', true];
        yield 'next minor' => ['2.3.12', '2.4.0', true];
        yield 'same version' => ['2.3.12', '2.3.12', false];
        yield 'older patch' => ['2.3.12', '2.3.11', false];
        yield 'next major' => ['2.3.12', '3.0.0', false];
        yield 'prerelease' => ['2.3.12', '2.4.0-beta.1', false];
        yield 'build metadata only' => ['2.3.12', '2.3.12+build', false];
    }

    #[DataProvider('installability')]
    public function testInstallability(string $current, string $candidate, bool $expected): void
    {
        FileSystem::write($this->registryFile, Json::encode(['currentRelease' => $current]));
        $updater = $this->updater();

        $this->assertSame($expected, $this->call($updater, 'isVersionInstallable', $candidate));
    }

    public function testIgnoredFilesAreNotCopied(): void
    {
        $updater = $this->updater(['ignore' => ['site/*', 'cache/*', '*.log']]);

        $this->assertFalse($this->call($updater, 'isCopiable', 'site/content/page.md'));
        $this->assertFalse($this->call($updater, 'isCopiable', 'cache/data'));
        $this->assertFalse($this->call($updater, 'isCopiable', 'debug.log'));
        $this->assertTrue($this->call($updater, 'isCopiable', 'formwork/src/Cms/App.php'));
    }

    public function testDeletableFilesAreTheOnesInInstalledDirectoriesThatTheReleaseDoesNotContain(): void
    {
        $root = TESTS_TMP_PATH . '/updater/install';
        FileSystem::createDirectory($root . '/dir', recursive: true);
        FileSystem::write($root . '/dir/kept.php', 'x');
        FileSystem::write($root . '/dir/removed.php', 'x');
        FileSystem::createDirectory($root . '/dir/empty');
        FileSystem::createDirectory($root . '/dir/full');
        FileSystem::write($root . '/dir/full/file.php', 'x');

        $deletable = $this->call($this->updater(), 'findDeletableFiles', [$root . '/dir', $root . '/dir/kept.php']);

        $this->assertEqualsCanonicalizing([$root . '/dir/removed.php', $root . '/dir/empty'], array_values($deletable));
    }

    // Helpers

    /**
     * @param array<string, mixed> $options
     */
    private function updater(array $options = [], ?Client $client = null): Updater
    {
        $updater = new Updater([
            'registryFile'        => $this->registryFile,
            'tempFile'            => $this->tempFile,
            'time'                => 900,
            'force'               => false,
            'preferDistAssets'    => true,
            'lockTimeout'         => 300,
            'cleanupAfterInstall' => false,
            'ignore'              => [],
            ...$options,
        ]);

        if ($client !== null) {
            (new ReflectionProperty($updater, 'client'))->setValue($updater, $client);
        }

        return $updater;
    }

    /**
     * @param array<string, mixed> $release
     */
    private function updaterWithRelease(array $release): Updater
    {
        $updater = $this->updater();
        (new ReflectionProperty($updater, 'release'))->setValue($updater, $release + ['name' => 'x', 'tag' => '9.9.9', 'date' => 0, 'archive' => 'x']);
        return $updater;
    }

    private function call(Updater $updater, string $method, mixed ...$arguments): mixed
    {
        return (new ReflectionMethod($updater, $method))->invoke($updater, ...$arguments);
    }

    private function tag(int $major = 0, int $minor = 0): string
    {
        $version = SemVer::fromString(App::VERSION);

        return sprintf('%d.%d.0', $version->major() + $major, $major > 0 ? 0 : $version->minor() + $minor);
    }

    /**
     * @param list<array<string, string>> $assets
     *
     * @return Client&MockObject
     */
    private function clientWithRelease(string $tag, array $assets = [], string $publishedAt = '2024-05-01T10:00:00Z', ?int $expectedFetches = null, bool $mock = false): Client
    {
        return $this->clientReturning($this->releaseJson($tag, $assets, $publishedAt), $expectedFetches, $mock);
    }

    /**
     * @return Client&MockObject
     */
    private function clientReturning(string $json, ?int $expectedFetches = null, bool $mock = false): Client
    {
        if ($expectedFetches === null && !$mock) {
            $client = $this->createStub(Client::class);
            $client->method('fetch')->willReturnCallback(fn(): Response => $this->response($json));
            return $client;
        }

        $client = $this->createMock(Client::class);
        $client->expects($expectedFetches === null ? $this->any() : $this->exactly($expectedFetches))->method('fetch')
            ->willReturnCallback(fn(): Response => $this->response($json));
        return $client;
    }

    private function response(string $json): Response
    {
        return new Response($json);
    }

    /**
     * @param list<array<string, string>> $assets
     */
    private function releaseJson(string $tag, array $assets = [], string $publishedAt = '2024-05-01T10:00:00Z'): string
    {
        return Json::encode([
            'name'         => 'Release name',
            'tag_name'     => $tag,
            'published_at' => $publishedAt,
            'zipball_url'  => 'https://api.example.test/zipball',
            'assets'       => $assets,
        ]);
    }
}
