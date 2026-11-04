<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Backup\Backupper;
use Formwork\Cache\ArrayCache;
use Formwork\Cms\App;
use Formwork\Http\Client;
use Formwork\Http\JsonResponse;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Http\ResponseHeaders;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Controllers\UpdatesController;
use Formwork\Parsers\Json;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\Fixtures\BuildsPanelControllers;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Updater\SemVer;
use Formwork\Updater\Updater;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use ZipArchive;

#[CoversClass(UpdatesController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class UpdatesControllerTest extends TestCase
{
    use BuildsPanelControllers;

    private string $root;

    private string $backupPath;

    private ArrayCache $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = TESTS_TMP_PATH . '/installation';
        FileSystem::createDirectory($this->root . '/site', recursive: true);
        FileSystem::write($this->root . '/index.php', 'old index');

        $this->backupPath = TESTS_TMP_PATH . '/backups';

        $this->cache = new ArrayCache('pages');
        $this->cache->set('page', 'cached');
    }

    protected function tearDown(): void
    {
        $this->closePanelSession();
        parent::tearDown();
    }

    public function testCheckRequiresThePermission(): void
    {
        $response = $this->controller(['panel.updates.check' => false])->check($this->updater($this->nextMinor()));

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testUpdatePermissionDoesNotAllowChecking(): void
    {
        $response = $this->controller(['panel.updates.check' => false, 'panel.updates.update' => true])->check($this->updater($this->nextMinor()));

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testCheckReportsAvailableUpdates(): void
    {
        $response = $this->controller()->check($this->updater($this->nextMinor()));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::OK, $response->status());

        $data = Json::parse($response->content());

        $this->assertSame('success', $data['status']);
        $this->assertFalse($data['data']['uptodate']);
        $this->assertSame($this->nextMinor(), $data['data']['release']['tag']);
    }

    public function testCheckReportsWhenFormworkIsUpToDate(): void
    {
        $response = $this->controller()->check($this->updater(App::VERSION));

        $data = Json::parse($response->content());

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertTrue($data['data']['uptodate']);
        $this->assertArrayNotHasKey('release', $data['data']);
    }

    public function testCheckFailureIsReportedAsAnError(): void
    {
        $response = $this->controller()->check($this->updater(App::VERSION, unreachable: true));

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
        $this->assertSame('error', Json::parse($response->content())['status']);
    }

    public function testUpdateRequiresThePermission(): void
    {
        $response = $this->controller(['panel.updates.update' => false])->update($this->updater($this->nextMinor()), $this->cache, $this->backupper());

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
        $this->assertFileDoesNotExist($this->root . '/updated.txt');
        $this->assertDirectoryDoesNotExist($this->backupPath);
    }

    public function testCheckPermissionDoesNotAllowUpdating(): void
    {
        $response = $this->controller(['panel.updates.check' => true, 'panel.updates.update' => false])->update($this->updater($this->nextMinor()), $this->cache, $this->backupper());

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
        $this->assertFileDoesNotExist($this->root . '/updated.txt');
    }

    public function testUpdateInstallsTheReleaseAfterMakingABackup(): void
    {
        $response = $this->controller()->update($this->updater($this->nextMinor()), $this->cache, $this->backupper());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertSame('updated', FileSystem::read($this->root . '/updated.txt'));
        $this->assertCount(1, glob($this->backupPath . '/*.zip') ?: []);
    }

    public function testBackupIsSkippedWhenDisabled(): void
    {
        $response = $this->controller(system: ['updates' => ['backupBefore' => false]])->update($this->updater($this->nextMinor()), $this->cache, $this->backupper());

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertFileExists($this->root . '/updated.txt');
        $this->assertDirectoryDoesNotExist($this->backupPath);
    }

    public function testBackupFailureStopsTheUpdate(): void
    {
        FileSystem::write($this->backupPath, 'not a directory');

        $response = $this->controller()->update($this->updater($this->nextMinor()), $this->cache, $this->backupper());

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
        $this->assertSame('error', Json::parse($response->content())['status']);
        $this->assertFileDoesNotExist($this->root . '/updated.txt');
    }

    public function testInstallationFailureIsReportedAsAnError(): void
    {
        $response = $this->controller(system: ['updates' => ['backupBefore' => false]])
            ->update($this->updater($this->nextMinor(), archive: false), $this->cache, $this->backupper());

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
        $this->assertSame('error', Json::parse($response->content())['status']);
        $this->assertTrue($this->cache->has('page'));
    }

    public function testPagesCacheIsClearedAfterUpdatingWhenEnabled(): void
    {
        $this->controller(system: ['cache' => ['enabled' => true], 'updates' => ['backupBefore' => false]])
            ->update($this->updater($this->nextMinor()), $this->cache, $this->backupper());

        $this->assertFalse($this->cache->has('page'));
    }

    public function testPagesCacheIsLeftUntouchedWhenDisabled(): void
    {
        $this->controller(system: ['cache' => ['enabled' => false], 'updates' => ['backupBefore' => false]])
            ->update($this->updater($this->nextMinor()), $this->cache, $this->backupper());

        $this->assertTrue($this->cache->has('page'));
    }

    public function testUpdateOfAnUpToDateInstallationDoesNotReportAnInstallation(): void
    {
        $response = $this->controller(system: ['updates' => ['backupBefore' => false]])
            ->update($this->updater(App::VERSION), $this->cache, $this->backupper());

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertFileDoesNotExist($this->root . '/updated.txt');
        $this->assertNotSame('installed', Json::parse($response->content())['message'] ?? null);
    }

    /**
     * @param array<string, bool>  $permissions
     * @param array<string, mixed> $system
     */
    private function controller(array $permissions = ['panel.updates.check' => true, 'panel.updates.update' => true], array $system = []): UpdatesController
    {
        return $this->makeController(UpdatesController::class, $permissions, $system);
    }

    private function backupper(): Backupper
    {
        return new Backupper(new Request([], [], [], [], []), [
            'path'             => $this->backupPath,
            'name'             => 'backup',
            'maxExecutionTime' => 60,
            'maxFiles'         => 10,
            'ignore'           => [],
        ], $this->root, App::instance());
    }

    private function nextMinor(): string
    {
        $semVer = SemVer::fromString(App::VERSION);

        return sprintf('%d.%d.0', $semVer->major(), $semVer->minor() + 1);
    }

    private function updater(string $tag, bool $unreachable = false, bool $archive = true): Updater
    {
        $client = $this->createStub(Client::class);

        if ($unreachable) {
            $client->method('fetch')->willThrowException(new RuntimeException('Network is unreachable'));
        } else {
            $client->method('fetch')->willReturn(new Response(Json::encode([
                'name'         => 'Release',
                'tag_name'     => $tag,
                'published_at' => '2024-05-01T10:00:00Z',
                'zipball_url'  => 'https://api.example.test/zipball',
                'assets'       => [],
            ])));
        }

        $client->method('fetchHeaders')->willReturn(new ResponseHeaders(['ETag' => '"etag"']));
        $client->method('download')->willReturnCallback(static function (string $uri, string $file) use ($archive): void {
            if ($archive) {
                $zip = new ZipArchive();
                $zip->open($file, ZipArchive::CREATE);
                $zip->addFromString('updated.txt', 'updated');
                $zip->close();
            }
        });

        return new Updater([
            'registryFile'        => TESTS_TMP_PATH . '/updates.json',
            'tempFile'            => TESTS_TMP_PATH . '/update.zip',
            'time'                => 900,
            'force'               => true,
            'preferDistAssets'    => true,
            'lockTimeout'         => 300,
            'cleanupAfterInstall' => false,
            'ignore'              => ['site/*'],
        ], $this->root, $client);
    }
}
