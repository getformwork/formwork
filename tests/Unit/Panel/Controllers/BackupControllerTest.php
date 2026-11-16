<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Backup\Backupper;
use Formwork\Cms\App;
use Formwork\Http\FileResponse;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Panel\Controllers\AbstractController;
use Formwork\Panel\Controllers\BackupController;
use Formwork\Parsers\Json;
use Formwork\Router\RouteParams;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\BuildsPanelControllers;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

#[CoversClass(BackupController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class BackupControllerTest extends TestCase
{
    use BuildsPanelControllers;

    private string $backupPath;

    private string $secretFile;

    private BackupController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupPath = FileSystem::joinPaths(TESTS_TMP_PATH, 'backups');
        FileSystem::createDirectory($this->backupPath);
        FileSystem::write(FileSystem::joinPaths($this->backupPath, 'backup.zip'), 'backup');
        FileSystem::write(FileSystem::joinPaths($this->backupPath, 'other.zip'), 'other');
        FileSystem::createDirectory(FileSystem::joinPaths($this->backupPath, 'nested'));
        FileSystem::write(FileSystem::joinPaths($this->backupPath, 'nested', 'inner.zip'), 'inner');

        $this->secretFile = FileSystem::joinPaths(TESTS_TMP_PATH, 'secret.txt');
        FileSystem::write($this->secretFile, 'secret');
    }

    protected function tearDown(): void
    {
        $this->closePanelSession();
        parent::tearDown();
    }

    public function testBackupsAreMadeAndDescribed(): void
    {
        $response = $this->call($this->makeController(BackupController::class, services: [Backupper::class => $this->backupper($this->backupPath)]), 'make');

        $this->assertOutcome(['status' => 200, 'json' => ['status' => 'success', 'data' => ['maxFiles' => 10]]], $response);

        $filename = Json::parse($response->content())['data']['filename'];
        $this->assertMatchesRegularExpression('/^test-\d{8}-\d{6}\.zip$/', $filename);
        $this->assertFileExists(FileSystem::joinPaths($this->backupPath, $filename));
    }

    public function testBackupFailuresAreReported(): void
    {
        // The backup path is a file, so the archive cannot be created
        $response = $this->call($this->makeController(BackupController::class, services: [Backupper::class => $this->backupper($this->secretFile)]), 'make');

        $this->assertOutcome(['status' => 500, 'json' => ['status' => 'error']], $response);
    }

    public function testBackupsCanBeDownloadedWithTheDownloadPermission(): void
    {
        $response = $this->download('backup.zip');

        $this->assertInstanceOf(FileResponse::class, $response);
        $this->assertSame(FileSystem::joinPaths($this->backupPath, 'backup.zip'), $this->responseFile($response));
    }

    #[DataProvider('escapingNameProvider')]
    public function testDownloadCannotLeaveTheBackupDirectory(string $name): void
    {
        $name = $this->expandName($name);

        $response = $this->download($name);

        $this->assertNotInstanceOf(FileResponse::class, $response);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastMessageType());
    }

    #[DataProvider('escapingNameProvider')]
    public function testDeleteCannotLeaveTheBackupDirectory(string $name): void
    {
        $name = $this->expandName($name);

        $response = $this->delete($name);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastMessageType());
        $this->assertFileExists($this->secretFile);
        $this->assertFileExists(FileSystem::joinPaths($this->backupPath, 'backup.zip'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function escapingNameProvider(): iterable
    {
        yield 'parent directory file' => ['../secret.txt'];
        yield 'parent directory file with a Windows separator' => ['..\secret.txt'];
        yield 'deep traversal' => ['{deep}'];
        yield 'deep traversal with Windows separators' => ['{deep-windows}'];
        yield 'traversal after a directory' => ['nested/../../secret.txt'];
        yield 'traversal with Windows separators after a directory' => ['nested\..\..\secret.txt'];
        yield 'mixed separators' => ['..\../secret.txt'];
        yield 'absolute path' => ['{secret}'];
        yield 'Windows absolute path' => ['C:\Windows\win.ini'];
        yield 'file in a subdirectory' => ['nested/inner.zip'];
        yield 'file in a subdirectory with a Windows separator' => ['nested\inner.zip'];
        yield 'backup directory itself' => ['.'];
        yield 'parent directory' => ['..'];
        yield 'empty name' => [''];
        yield 'trailing separator' => ['backup.zip/'];
        yield 'file name with a null byte' => ["backup.zip\0../../secret.txt"];
    }

    public function testDownloadOfAMissingBackupIsReportedAsAnError(): void
    {
        $response = $this->download('missing.zip');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastMessageType());
    }

    public function testDownloadWithAnInvalidEncodingIsReportedAsAnError(): void
    {
        $controller = $this->controller();

        $response = $controller->download(new RouteParams(['backup' => '!!!not base64!!!']));

        $this->assertNotInstanceOf(FileResponse::class, $response);
    }

    public function testBackupsCanBeDeletedWithTheDeletePermission(): void
    {
        $response = $this->delete('backup.zip');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('success', $this->lastMessageType());
        $this->assertFileDoesNotExist(FileSystem::joinPaths($this->backupPath, 'backup.zip'));
        $this->assertFileExists(FileSystem::joinPaths($this->backupPath, 'other.zip'));
    }

    public function testDeleteOfAMissingBackupIsReportedAsAnError(): void
    {
        $response = $this->delete('missing.zip');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastMessageType());
        $this->assertFileExists(FileSystem::joinPaths($this->backupPath, 'backup.zip'));
    }

    public function testDeleteDoesNotRemoveDirectories(): void
    {
        $this->delete('nested');

        $this->assertDirectoryExists(FileSystem::joinPaths($this->backupPath, 'nested'));
        $this->assertFileExists(FileSystem::joinPaths($this->backupPath, 'nested', 'inner.zip'));
    }

    /**
     * Payloads must only ever point to the sentinel file in the temporary directory, never to real system files
     */
    private function expandName(string $name): string
    {
        $relative = ltrim($this->secretFile, '/');
        $deep = str_repeat('../', 20) . $relative;

        return strtr($name, [
            '{secret}'       => $this->secretFile,
            '{deep}'         => $deep,
            '{deep-windows}' => str_replace('/', '\\', $deep),
        ]);
    }

    /**
     * Create a backupper of a small installation, saving the backups to the given path
     */
    private function backupper(string $path): Backupper
    {
        $root = FileSystem::joinPaths(TESTS_TMP_PATH, 'installation');
        FileSystem::createDirectory($root);
        FileSystem::write(FileSystem::joinPaths($root, 'index.php'), '<?php');

        $app = App::instance();

        return new Backupper($app->getService(Request::class), [...$app->config()->getArray('system.backup'), 'path' => $path, 'name' => 'test'], $root, $app);
    }

    private function download(string $name): Response
    {
        return $this->controller()->download(new RouteParams(['backup' => base64_encode($name)]));
    }

    private function delete(string $name): RedirectResponse
    {
        return $this->controller()->delete(new RouteParams(['backup' => base64_encode($name)]));
    }

    private function controller(): BackupController
    {
        $this->controller = $this->makeController(BackupController::class, ['backup' => ['path' => $this->backupPath]]);

        return $this->controller;
    }

    private function lastMessageType(): ?string
    {
        $reflectionProperty = new ReflectionProperty(AbstractController::class, 'panel');
        $panel = $reflectionProperty->getValue($this->controller);
        $notifications = $panel->notifications();

        return $notifications === [] ? null : end($notifications)['type'];
    }

    private function responseFile(FileResponse $fileResponse): string
    {
        $reflectionProperty = new ReflectionProperty(FileResponse::class, 'path');

        return (string) $reflectionProperty->getValue($fileResponse);
    }
}
