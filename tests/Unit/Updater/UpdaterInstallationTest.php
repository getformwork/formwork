<?php

namespace Formwork\Tests\Unit\Updater;

use Formwork\Cms\App;
use Formwork\Http\Client;
use Formwork\Http\Response;
use Formwork\Http\ResponseHeaders;
use Formwork\Parsers\Json;
use Formwork\Tests\TestCase;
use Formwork\Tests\TestInstallation;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Updater\SemVer;
use Formwork\Updater\Updater;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use ZipArchive;

#[CoversClass(Updater::class)]
#[UsesTempDirectory]
final class UpdaterInstallationTest extends TestCase
{
    private string $root;

    private string $archive;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = TESTS_TMP_PATH . '/installation';
        FileSystem::createDirectory($this->root . '/formwork/src', recursive: true);
        FileSystem::createDirectory($this->root . '/site/pages', recursive: true);
        FileSystem::write($this->root . '/index.php', 'old index');
        FileSystem::write($this->root . '/formwork/src/Old.php', 'obsolete file');
        FileSystem::write($this->root . '/formwork/src/Kept.php', 'old version');
        FileSystem::write($this->root . '/site/pages/page.md', 'user content');

        $this->archive = TESTS_TMP_PATH . '/release.zip';
        $this->buildArchive([
            'index.php'              => 'new index',
            'formwork/src/Kept.php'  => 'new version',
            'formwork/src/Added.php' => 'added file',
            'site/pages/page.md'     => 'content shipped with the release',
        ]);
    }

    public function testReleaseIsInstalledInTheGivenRoot(): void
    {
        $this->assertTrue($this->updater()->update(force: true));

        $this->assertSame('new index', FileSystem::read($this->root . '/index.php'));
        $this->assertSame('new version', FileSystem::read($this->root . '/formwork/src/Kept.php'));
        $this->assertSame('added file', FileSystem::read($this->root . '/formwork/src/Added.php'));
    }

    public function testIgnoredPathsAreNotTouched(): void
    {
        $this->updater()->update(force: true);

        $this->assertSame('user content', FileSystem::read($this->root . '/site/pages/page.md'));
    }

    public function testTheRegistryRecordsTheInstalledRelease(): void
    {
        $this->updater()->update(force: true);

        $registry = Json::parseFile(TESTS_TMP_PATH . '/updates.json');
        $this->assertSame($this->nextMinor(), $registry['currentRelease']);
        $this->assertTrue($registry['upToDate']);
        $this->assertIsInt($registry['lastUpdate']);
    }

    public function testTemporaryFilesAreRemoved(): void
    {
        $this->updater()->update(force: true);

        $this->assertFileDoesNotExist(TESTS_TMP_PATH . '/update.zip');
        $this->assertFileDoesNotExist(TESTS_TMP_PATH . '/update.zip.lock');
    }

    public function testObsoleteFilesAreKeptUnlessCleanupIsRequested(): void
    {
        $this->updater()->update(force: true, cleanupAfterInstall: false);

        $this->assertFileExists($this->root . '/formwork/src/Old.php');
    }

    public function testObsoleteFilesOfInstalledDirectoriesAreRemovedWithCleanup(): void
    {
        $this->updater()->update(force: true, cleanupAfterInstall: true);

        $this->assertFileDoesNotExist($this->root . '/formwork/src/Old.php');
        $this->assertFileExists($this->root . '/formwork/src/Kept.php');
        $this->assertFileExists($this->root . '/site/pages/page.md', 'Ignored paths are never cleaned up');
    }

    public function testArchivesEscapingTheRootAreRefusedAndNothingLeavesTheInstallation(): void
    {
        $this->buildArchive(['../outside.txt' => 'payload']);

        try {
            $this->updater()->update(force: true);
            $this->fail('The archive must be refused.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('invalid destination', $exception->getMessage());
        }

        $this->assertFileDoesNotExist(TESTS_TMP_PATH . '/outside.txt');
        $this->assertFileDoesNotExist(dirname(TestInstallation::rootPath()) . '/outside.txt');
    }

    public function testTheRealInstallationIsNeverTouched(): void
    {
        $before = FileSystem::read(TestInstallation::rootPath() . '/index.php');

        $this->updater()->update(force: true);

        $this->assertSame($before, FileSystem::read(TestInstallation::rootPath() . '/index.php'));
        $this->assertFileDoesNotExist(TestInstallation::rootPath() . '/formwork/src/Added.php');
    }

    private function updater(): Updater
    {
        $client = $this->createStub(Client::class);
        $client->method('fetch')->willReturn(new Response(Json::encode([
            'name'         => 'Release',
            'tag_name'     => $this->nextMinor(),
            'published_at' => '2024-05-01T10:00:00Z',
            'zipball_url'  => 'https://api.example.test/zipball',
            'assets'       => [],
        ])));
        $client->method('download')->willReturnCallback(function (string $uri, string $file): void {
            copy($this->archive, $file);
        });
        $client->method('fetchHeaders')->willReturn(new ResponseHeaders(['ETag' => '"etag"']));

        return new Updater([
            'registryFile'        => TESTS_TMP_PATH . '/updates.json',
            'tempFile'            => TESTS_TMP_PATH . '/update.zip',
            'time'                => 900,
            'force'               => false,
            'preferDistAssets'    => true,
            'lockTimeout'         => 300,
            'cleanupAfterInstall' => false,
            'ignore'              => ['site/*'],
        ], $this->root, $client);
    }

    private function nextMinor(): string
    {
        $version = SemVer::fromString(App::VERSION);

        return sprintf('%d.%d.0', $version->major(), $version->minor() + 1);
    }

    /**
     * @param array<string, string> $files
     */
    private function buildArchive(array $files): void
    {
        if (file_exists($this->archive)) {
            unlink($this->archive);
        }

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($this->archive, ZipArchive::CREATE));
        foreach ($files as $name => $content) {
            // Release archives contain an entry for every directory
            $directory = dirname($name);
            if ($directory !== '.' && !str_starts_with($directory, '..')) {
                $zip->addEmptyDir($directory);
            }
            $zip->addFromString($name, $content);
        }
        $zip->close();
    }
}
