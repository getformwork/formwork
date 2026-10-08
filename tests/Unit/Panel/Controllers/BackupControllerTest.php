<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Http\FileResponse;
use Formwork\Http\RedirectResponse;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Controllers\BackupController;
use Formwork\Router\RouteParams;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\Fixtures\BuildsPanelControllers;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(BackupController::class)]
final class BackupControllerTest extends TestCase
{
    use BuildsPanelControllers;

    private string $backupPath;

    private string $secretFile;

    private BackupController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();

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
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testBackupsCanBeDownloadedWithTheDownloadPermission(): void
    {
        $response = $this->download('backup.zip', ['panel.backup.download' => true]);

        $this->assertInstanceOf(FileResponse::class, $response);
        $this->assertSame(FileSystem::joinPaths($this->backupPath, 'backup.zip'), $this->responseFile($response));
    }

    public function testDownloadRequiresTheDownloadPermission(): void
    {
        $response = $this->download('backup.zip', ['panel.backup' => false]);

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testMakingOrDeletingBackupsDoesNotGrantDownloads(): void
    {
        $response = $this->download('backup.zip', ['panel.backup' => false, 'panel.backup.make' => true, 'panel.backup.delete' => true]);

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    #[DataProvider('escapingNameProvider')]
    public function testDownloadCannotLeaveTheBackupDirectory(string $name): void
    {
        $name = $this->expandName($name);

        $response = $this->download($name, ['panel.backup.download' => true]);

        $this->assertNotInstanceOf(FileResponse::class, $response);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastMessageType());
    }

    #[DataProvider('escapingNameProvider')]
    public function testDeleteCannotLeaveTheBackupDirectory(string $name): void
    {
        $name = $this->expandName($name);

        $response = $this->delete($name, ['panel.backup.delete' => true]);

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
        yield 'parent directory file with a Windows separator' => ['..\\secret.txt'];
        yield 'deep traversal' => ['{deep}'];
        yield 'deep traversal with Windows separators' => ['{deep-windows}'];
        yield 'traversal after a directory' => ['nested/../../secret.txt'];
        yield 'traversal with Windows separators after a directory' => ['nested\\..\\..\\secret.txt'];
        yield 'mixed separators' => ['..\\../secret.txt'];
        yield 'absolute path' => ['{secret}'];
        yield 'Windows absolute path' => ['C:\\Windows\\win.ini'];
        yield 'file in a subdirectory' => ['nested/inner.zip'];
        yield 'file in a subdirectory with a Windows separator' => ['nested\\inner.zip'];
        yield 'backup directory itself' => ['.'];
        yield 'parent directory' => ['..'];
        yield 'empty name' => [''];
        yield 'trailing separator' => ['backup.zip/'];
        yield 'file name with a null byte' => ["backup.zip\0../../secret.txt"];
    }

    public function testDownloadOfAMissingBackupIsReportedAsAnError(): void
    {
        $response = $this->download('missing.zip', ['panel.backup.download' => true]);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastMessageType());
    }

    public function testDownloadWithAnInvalidEncodingIsReportedAsAnError(): void
    {
        $controller = $this->controller(['panel.backup.download' => true]);

        $response = $controller->download(new RouteParams(['backup' => '!!!not base64!!!']));

        $this->assertNotInstanceOf(FileResponse::class, $response);
    }

    public function testBackupsCanBeDeletedWithTheDeletePermission(): void
    {
        $response = $this->delete('backup.zip', ['panel.backup.delete' => true]);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('success', $this->lastMessageType());
        $this->assertFileDoesNotExist(FileSystem::joinPaths($this->backupPath, 'backup.zip'));
        $this->assertFileExists(FileSystem::joinPaths($this->backupPath, 'other.zip'));
    }

    public function testDeleteRequiresTheDeletePermission(): void
    {
        $response = $this->delete('backup.zip', ['panel.backup' => false]);

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
        $this->assertFileExists(FileSystem::joinPaths($this->backupPath, 'backup.zip'));
    }

    public function testTheDownloadPermissionDoesNotAllowDeletingBackups(): void
    {
        $response = $this->delete('backup.zip', ['panel.backup' => false, 'panel.backup.download' => true]);

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
        $this->assertFileExists(FileSystem::joinPaths($this->backupPath, 'backup.zip'));
    }

    public function testTheDeletePermissionDoesNotAllowDownloadingBackups(): void
    {
        $response = $this->download('backup.zip', ['panel.backup' => false, 'panel.backup.delete' => true]);

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testDeleteOfAMissingBackupIsReportedAsAnError(): void
    {
        $response = $this->delete('missing.zip', ['panel.backup.delete' => true]);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastMessageType());
        $this->assertFileExists(FileSystem::joinPaths($this->backupPath, 'backup.zip'));
    }

    public function testDeleteDoesNotRemoveDirectories(): void
    {
        $this->delete('nested', ['panel.backup.delete' => true]);

        $this->assertDirectoryExists(FileSystem::joinPaths($this->backupPath, 'nested'));
        $this->assertFileExists(FileSystem::joinPaths($this->backupPath, 'nested', 'inner.zip'));
    }

    public function testMakingBackupsRequiresThePermission(): void
    {
        $controller = $this->controller(['panel.backup' => false]);

        $response = $controller->make((new \ReflectionClass(\Formwork\Backup\Backupper::class))->newInstanceWithoutConstructor());

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
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
     * @param array<string, bool> $permissions
     */
    private function download(string $name, array $permissions): \Formwork\Http\Response
    {
        return $this->controller($permissions)->download(new RouteParams(['backup' => base64_encode($name)]));
    }

    /**
     * @param array<string, bool> $permissions
     */
    private function delete(string $name, array $permissions): \Formwork\Http\Response
    {
        return $this->controller($permissions)->delete(new RouteParams(['backup' => base64_encode($name)]));
    }

    /**
     * @param array<string, bool> $permissions
     */
    private function controller(array $permissions): BackupController
    {
        $this->controller = $this->makeController(BackupController::class, $permissions, ['backup' => ['path' => $this->backupPath]]);

        return $this->controller;
    }

    private function lastMessageType(): ?string
    {
        $reflection = new \ReflectionProperty(\Formwork\Panel\Controllers\AbstractController::class, 'panel');
        $panel = $reflection->getValue($this->controller);
        $notifications = $panel->notifications();

        return $notifications === [] ? null : end($notifications)['type'];
    }

    private function responseFile(FileResponse $response): string
    {
        $property = new \ReflectionProperty(FileResponse::class, 'path');

        return (string) $property->getValue($response);
    }
}
