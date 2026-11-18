<?php

namespace Formwork\Tests\Unit\Backup;

use Formwork\Backup\Backupper;
use Formwork\Cms\App;
use Formwork\Exceptions\TranslatedException;
use Formwork\Http\Request;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;
use ZipArchive;

#[CoversClass(Backupper::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class BackupperTest extends TestCase
{
    private string $backupPath;

    private string $projectPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupPath = TESTS_TMP_PATH . '/backups';
        $this->projectPath = TESTS_TMP_PATH . '/project';
        FileSystem::createDirectory($this->projectPath . '/sub', recursive: true);
        FileSystem::write($this->projectPath . '/composer.json', '{}');
        FileSystem::write($this->projectPath . '/ignored.txt', 'ignored');
        FileSystem::write($this->projectPath . '/sub/file.txt', 'nested');
    }

    public function testBackupArchiveIsCreatedInTheConfiguredPath(): void
    {
        $destination = $this->backupper()->backup('test-backup', 'example.test');

        $this->assertSame($this->backupPath, dirname($destination));
        $this->assertMatchesRegularExpression('/^test-backup-\d{8}-\d{6}\.zip$/', basename($destination));
        $this->assertFileExists($destination);
    }

    public function testBackupContainsTheNonIgnoredFilesOnly(): void
    {
        $destination = $this->backupper()->backup('contents', 'example.test');

        $zipArchive = new ZipArchive();
        $this->assertTrue($zipArchive->open($destination));
        $entries = [];
        for ($i = 0; $i < $zipArchive->numFiles; $i++) {
            $entries[] = $zipArchive->getNameIndex($i);
        }
        $zipArchive->close();

        $this->assertSame(['composer.json'], $entries);
    }

    public function testBackupDirectoryIsCreatedRecursively(): void
    {
        $path = $this->backupPath . '/nested/deeper';

        $this->backupper(['path' => $path])->backup('nested', 'example.test');

        $this->assertDirectoryExists($path);
    }

    public function testNameIsInterpolated(): void
    {
        $destination = $this->backupper()->backup('{{hostname}}-{{context}}', 'www.example.test');

        $this->assertStringStartsWith('www-example-test-cli-', basename($destination));
    }

    public function testDefaultNameComesFromTheOptions(): void
    {
        $destination = $this->backupper(['name' => '{{hostname}}-formwork-backup'])->backup(null, 'another.example.test');

        $this->assertStringStartsWith('another-example-test-formwork-backup-', basename($destination));
    }

    public function testHostnameComesFromTheOptionsWhenNotGiven(): void
    {
        $destination = $this->backupper(['name' => '{{hostname}}', 'hostname' => 'configured.example'])->backup();

        $this->assertStringStartsWith('configured-example-', basename($destination));
    }

    public function testVersionPlaceholderIsInterpolated(): void
    {
        $destination = $this->backupper()->backup('v{{version}}', 'example.test');

        $this->assertStringStartsWith('v' . App::VERSION . '-', basename($destination));
    }

    public function testRandomPlaceholdersProduceDifferentNames(): void
    {
        $first = $this->backupper()->backup('{{random}}', 'example.test');
        $second = $this->backupper()->backup('{{random}}', 'example.test');

        $this->assertNotSame(basename($first), basename($second));
    }

    public function testLongNamesAreTruncatedAndStillEndWithTheSuffix(): void
    {
        $destination = $this->backupper()->backup(str_repeat('a', 200), 'example.test');

        $this->assertSame(75, strlen(basename($destination)));
        $this->assertMatchesRegularExpression('/^a+-\d{8}-\d{6}\.zip$/', basename($destination));
    }

    public function testTrailingSeparatorsAreTrimmedBeforeTheSuffix(): void
    {
        // The name is cut after 55 characters (75 minus the suffix), in the middle of the separators
        $name = str_repeat('a', 52) . '-_-_-_-' . str_repeat('b', 100);

        $destination = $this->backupper()->backup($name, 'example.test');

        $this->assertMatchesRegularExpression('/^a{52}-\d{8}-\d{6}\.zip$/', basename($destination));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidNamesProvider(): iterable
    {
        yield 'path traversal' => ['../escape'];
        yield 'nested directory' => ['sub/backup'];
        yield 'absolute path' => ['/tmp/backup'];
        yield 'backslash' => ['sub\backup'];
        yield 'space' => ['my backup'];
        yield 'newline' => ["backup\nname"];
        yield 'null byte' => ["backup\0name"];
        yield 'unicode' => ['bäckup'];
        yield 'unknown placeholder' => ['{{unknown}}'];
        yield 'shell metacharacters' => ['backup;rm'];
    }

    #[DataProvider('invalidNamesProvider')]
    public function testInvalidNamesAreRejectedBeforeAnythingIsWritten(string $name): void
    {
        $unexpectedValueException = $this->captureException(UnexpectedValueException::class, fn() => $this->backupper()->backup($name, 'example.test'));
        $this->assertStringContainsString('invalid characters', $unexpectedValueException->getMessage());

        $this->assertSame([], $this->archives());
        $this->assertFileDoesNotExist(TESTS_TMP_PATH . '/escape-' . date('Ymd') . '.zip');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function hostnamesProvider(): iterable
    {
        yield 'ipv4' => ['192.168.1.10'];
        yield 'ipv6' => ['[2001:db8::1]'];
        yield 'localhost' => ['localhost'];
    }

    #[DataProvider('hostnamesProvider')]
    public function testEveryHostnameTheRequestCanReportProducesAValidArchiveName(string $hostname): void
    {
        $destination = $this->backupper(['name' => '{{hostname}}'])->backup(null, $hostname);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9._-]+\.zip$/', basename($destination));
    }

    public function testNameFallsBackToTheRequestHostWhenNoHostnameIsGiven(): void
    {
        $request = new Request(server: ['SERVER_NAME' => 'request.example.test', 'HTTP_HOST' => 'request.example.test']);

        $destination = $this->backupper(['name' => '{{hostname}}'], $request)->backup();

        $this->assertStringStartsWith('request-example-test-', basename($destination));
    }

    public function testMaxExecutionTimeIsRestoredAfterTheBackup(): void
    {
        $before = ini_get('max_execution_time');

        $this->backupper(['maxExecutionTime' => '123'])->backup('timing', 'example.test');

        $this->assertSame($before, ini_get('max_execution_time'));
    }

    public function testMaxExecutionTimeIsRestoredWhenTheBackupFails(): void
    {
        $before = ini_get('max_execution_time');

        $this->captureException(UnexpectedValueException::class, fn() => $this->backupper(['maxExecutionTime' => '123'])->backup('bad name', 'example.test'));

        $this->assertSame($before, ini_get('max_execution_time'));
    }

    public function testArchivesThatCannotBeCreatedAreReported(): void
    {
        // The backup path is a file, so the archive cannot be created in it
        FileSystem::write(TESTS_TMP_PATH . '/not-a-directory', '');

        $this->expectException(TranslatedException::class);
        $this->backupper(['path' => TESTS_TMP_PATH . '/not-a-directory'])->backup(hostname: 'example.test');
    }

    /**
     * @param list<string> $existing Backups older than the new one, from the oldest
     * @param list<string> $deleted
     */
    #[DataProvider('exceedingBackupsProvider')]
    public function testOldBackupsAreDeletedKeepingTheNewestOnes(array $existing, array $deleted): void
    {
        FileSystem::createDirectory($this->backupPath, recursive: true);
        foreach ($existing as $i => $name) {
            $file = $this->backupPath . "/{$name}.zip";
            FileSystem::write($file, 'x');
            touch($file, time() - 1000 + $i * 10);
        }

        $this->backupper(['maxFiles' => 2])->backup('newest', 'example.test');

        $remaining = array_map(basename(...), $this->archives());
        $this->assertCount(2, $remaining);
        foreach ($existing as $name) {
            $this->assertSame(!in_array($name, $deleted, true), in_array("{$name}.zip", $remaining, true), $name);
        }
    }

    /**
     * @return iterable<string, array{list<string>, list<string>}>
     */
    public static function exceedingBackupsProvider(): iterable
    {
        yield 'one backup in excess' => [['oldest', 'old'], ['oldest']];
        yield 'two backups in excess' => [['oldest', 'older', 'old'], ['oldest', 'older']];
    }

    public function testBackupsAreListedNewestFirst(): void
    {
        FileSystem::createDirectory($this->backupPath, recursive: true);
        $times = ['a.zip' => 1_000, 'b.zip' => 3_000, 'c.zip' => 2_000];
        foreach ($times as $name => $time) {
            FileSystem::write($this->backupPath . '/' . $name, 'x');
            touch($this->backupPath . '/' . $name, $time);
        }

        $backups = $this->backupper()->getBackups();

        $this->assertSame([3_000, 2_000, 1_000], array_keys($backups));
        $this->assertSame([$this->backupPath . '/b.zip', $this->backupPath . '/c.zip', $this->backupPath . '/a.zip'], array_values($backups));
    }

    public function testBackupsCreatedInTheSameSecondAreAllListed(): void
    {
        FileSystem::createDirectory($this->backupPath, recursive: true);
        foreach (['first.zip', 'second.zip', 'third.zip'] as $name) {
            FileSystem::write($this->backupPath . '/' . $name, 'x');
            touch($this->backupPath . '/' . $name, 5_000);
        }

        $this->assertCount(3, $this->backupper()->getBackups());
    }

    public function testSameSecondBackupsAreCountedWhenPruning(): void
    {
        FileSystem::createDirectory($this->backupPath, recursive: true);
        foreach (['a.zip', 'b.zip', 'c.zip', 'd.zip'] as $name) {
            FileSystem::write($this->backupPath . '/' . $name, 'x');
            touch($this->backupPath . '/' . $name, 5_000);
        }

        $this->backupper(['maxFiles' => 2])->backup('pruning', 'example.test');

        $this->assertCount(2, $this->archives());
    }

    public function testListingCreatesAMissingBackupDirectory(): void
    {
        $this->assertSame([], $this->backupper()->getBackups());
        $this->assertDirectoryExists($this->backupPath);
    }

    public function testListingCreatesNestedMissingBackupDirectories(): void
    {
        $path = $this->backupPath . '/a/b';

        $this->assertSame([], $this->backupper(['path' => $path])->getBackups());
        $this->assertDirectoryExists($path);
    }

    /**
     * @return list<string>
     */
    private function archives(): array
    {
        if (!is_dir($this->backupPath)) {
            return [];
        }
        return array_map(fn(string $file): string => $this->backupPath . '/' . $file, iterator_to_array(FileSystem::listFiles($this->backupPath), false));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function backupper(array $options = [], ?Request $request = null): Backupper
    {
        return new Backupper($request ?? new Request(), [
            'path'             => $this->backupPath,
            'name'             => '{{hostname}}-formwork-backup',
            'maxExecutionTime' => '180',
            'maxFiles'         => 10,
            'ignore'           => ['*/*', 'ignored.txt'],
            ...$options,
        ], $this->projectPath, App::instance());
    }
}
