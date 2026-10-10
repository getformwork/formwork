<?php

namespace Formwork\Tests\Unit\Backup;

use Formwork\Backup\Backupper;
use Formwork\Cms\App;
use Formwork\Http\Request;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;
use ZipArchive;

#[CoversClass(Backupper::class)]
final class BackupperTest extends TestCase
{
    private string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->backupPath = TESTS_TMP_PATH . '/backups';
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testBackupArchiveIsCreatedInTheConfiguredPath(): void
    {
        $destination = $this->backupper()->backup('test-backup', 'example.com');

        $this->assertSame($this->backupPath, dirname($destination));
        $this->assertMatchesRegularExpression('/^test-backup-\d{8}-\d{6}\.zip$/', basename($destination));
        $this->assertFileExists($destination);
    }

    public function testBackupContainsTheNonIgnoredFilesOnly(): void
    {
        $destination = $this->backupper()->backup('contents', 'example.com');

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($destination));
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = $zip->getNameIndex($i);
        }
        $zip->close();

        $this->assertSame(['composer.json'], $entries);
    }

    public function testBackupDirectoryIsCreatedRecursively(): void
    {
        $path = $this->backupPath . '/nested/deeper';

        $this->backupper(['path' => $path])->backup('nested', 'example.com');

        $this->assertDirectoryExists($path);
    }

    public function testNameIsInterpolated(): void
    {
        $destination = $this->backupper()->backup('{{hostname}}-{{context}}', 'www.example.com');

        $this->assertStringStartsWith('www-example-com-cli-', basename($destination));
    }

    public function testDefaultNameComesFromTheOptions(): void
    {
        $destination = $this->backupper(['name' => '{{hostname}}-formwork-backup'])->backup(null, 'example.org');

        $this->assertStringStartsWith('example-org-formwork-backup-', basename($destination));
    }

    public function testHostnameComesFromTheOptionsWhenNotGiven(): void
    {
        $destination = $this->backupper(['name' => '{{hostname}}', 'hostname' => 'configured.example'])->backup();

        $this->assertStringStartsWith('configured-example-', basename($destination));
    }

    public function testVersionPlaceholderIsInterpolated(): void
    {
        $destination = $this->backupper()->backup('v{{version}}', 'example.com');

        $this->assertStringStartsWith('v' . App::VERSION . '-', basename($destination));
    }

    public function testRandomPlaceholdersProduceDifferentNames(): void
    {
        $first = $this->backupper()->backup('{{random}}', 'example.com');
        $second = $this->backupper()->backup('{{random}}', 'example.com');

        $this->assertNotSame(basename($first), basename($second));
    }

    public function testLongNamesAreTruncatedAndStillEndWithTheSuffix(): void
    {
        $destination = $this->backupper()->backup(str_repeat('a', 200), 'example.com');

        $this->assertLessThanOrEqual(75, strlen(basename($destination)));
        $this->assertMatchesRegularExpression('/^a+-\d{8}-\d{6}\.zip$/', basename($destination));
    }

    public function testTrailingSeparatorsAreTrimmedBeforeTheSuffix(): void
    {
        $name = str_repeat('a', 40) . '--__--' . str_repeat('b', 100);

        $destination = $this->backupper()->backup($name, 'example.com');

        $this->assertDoesNotMatchRegularExpression('/[-_]{2,}\d{8}-\d{6}\.zip$/', basename($destination));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidNames(): iterable
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

    #[DataProvider('invalidNames')]
    public function testInvalidNamesAreRejectedBeforeAnythingIsWritten(string $name): void
    {
        try {
            $this->backupper()->backup($name, 'example.com');
            $this->fail('The backup name should have been rejected.');
        } catch (UnexpectedValueException $exception) {
            $this->assertStringContainsString('invalid characters', $exception->getMessage());
        }

        $this->assertSame([], $this->archives());
        $this->assertFileDoesNotExist(TESTS_TMP_PATH . '/escape-' . date('Ymd') . '.zip');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function hostnames(): iterable
    {
        yield 'ipv4' => ['192.168.1.10'];
        yield 'ipv6' => ['[2001:db8::1]'];
        yield 'localhost' => ['localhost'];
    }

    #[DataProvider('hostnames')]
    public function testEveryHostnameTheRequestCanReportProducesAValidArchiveName(string $hostname): void
    {
        $destination = $this->backupper(['name' => '{{hostname}}'])->backup(null, $hostname);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9._-]+\.zip$/', basename($destination));
    }

    public function testNameFallsBackToTheRequestHostWhenNoHostnameIsGiven(): void
    {
        $request = new Request([], [], [], [], ['SERVER_NAME' => 'request.example.com', 'HTTP_HOST' => 'request.example.com']);

        $destination = $this->backupper(['name' => '{{hostname}}'], $request)->backup();

        $this->assertStringStartsWith('request-example-com-', basename($destination));
    }

    public function testMaxExecutionTimeIsRestoredAfterTheBackup(): void
    {
        $before = ini_get('max_execution_time');

        $this->backupper(['maxExecutionTime' => '123'])->backup('timing', 'example.com');

        $this->assertSame($before, ini_get('max_execution_time'));
    }

    public function testMaxExecutionTimeIsRestoredWhenTheBackupFails(): void
    {
        $before = ini_get('max_execution_time');

        try {
            $this->backupper(['maxExecutionTime' => '123'])->backup('bad name', 'example.com');
        } catch (UnexpectedValueException) {
            // Expected
        }

        $this->assertSame($before, ini_get('max_execution_time'));
    }

    public function testOldBackupsAreDeletedKeepingTheNewestOnes(): void
    {
        FileSystem::createDirectory($this->backupPath, recursive: true);
        foreach (['oldest', 'older', 'old'] as $i => $name) {
            $file = $this->backupPath . "/{$name}.zip";
            FileSystem::write($file, 'x');
            touch($file, time() - 1000 + $i * 10);
        }

        $this->backupper(['maxFiles' => 2])->backup('newest', 'example.com');

        $remaining = array_map('basename', $this->archives());
        sort($remaining);
        $this->assertCount(2, $remaining);
        $this->assertContains('old.zip', $remaining);
        $this->assertNotContains('oldest.zip', $remaining);
        $this->assertNotContains('older.zip', $remaining);
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

        $this->backupper(['maxFiles' => 2])->backup('pruning', 'example.com');

        $this->assertLessThanOrEqual(2, count($this->archives()));
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
        return new Backupper($request ?? new Request([], [], [], [], []), [
            'path'             => $this->backupPath,
            'name'             => '{{hostname}}-formwork-backup',
            'maxExecutionTime' => '180',
            'maxFiles'         => 10,
            'ignore'           => $this->ignoreEverythingButComposerJson(),
            ...$options,
        ]);
    }

    /**
     * Ignore patterns restricting the archive to a single small file of the project
     *
     * @return list<string>
     */
    private function ignoreEverythingButComposerJson(): array
    {
        $patterns = ['*/*'];

        foreach (FileSystem::listContents(ROOT_PATH, FileSystem::LIST_ALL) as $item) {
            if ($item !== 'composer.json') {
                $patterns[] = $item;
            }
        }

        return $patterns;
    }
}
