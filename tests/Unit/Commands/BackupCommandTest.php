<?php

namespace Formwork\Tests\Unit\Commands;

use Formwork\Commands\BackupCommand;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Commands\Fixtures\RunsCommands;
use Formwork\Tests\UsesApplication;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BackupCommand::class)]
#[UsesApplication]
final class BackupCommandTest extends TestCase
{
    use RunsCommands;

    public function testHelpListsTheActions(): void
    {
        $result = $this->runCommand(BackupCommand::class);

        $this->assertSame(0, $result['exitCode']);
        $this->assertStringContainsString('Available Actions:', $result['output']);
        $this->assertStringContainsString('make', $result['output']);
        $this->assertStringContainsString('list', $result['output']);
    }

    public function testHelpOptionShowsTheHelp(): void
    {
        $result = $this->runCommand(BackupCommand::class, ['make', '--help']);

        $this->assertSame(0, $result['exitCode']);
        $this->assertStringContainsString('Available Actions:', $result['output']);
    }

    public function testUnknownActionsAreRejected(): void
    {
        $result = $this->runCommand(BackupCommand::class, ['bogus']);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('Invalid action: bogus.', $result['error'] . $result['output']);
    }

    public function testListWithoutBackupsReportsThatNoneExist(): void
    {
        $result = $this->runCommand(BackupCommand::class, ['list']);

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);
        $this->assertStringContainsString('No backups found.', $result['output']);
    }

    public function testListShowsTheAvailableBackupsFromTheNewest(): void
    {
        $result = $this->runCommand(BackupCommand::class, ['list'], environment: ['FORMWORK_TEST_BACKUPS' => '3']);

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);
        $this->assertStringContainsString('Available backups:', $result['output']);
        $this->assertStringNotContainsString('No backups found.', $result['output']);

        $positions = array_map(fn(int $i): int|false => strpos($result['output'], "seeded-{$i}.zip"), [1, 2, 3]);

        $this->assertNotContains(false, $positions);
        $this->assertLessThan($positions[1], $positions[2]);
        $this->assertLessThan($positions[0], $positions[1]);
    }

    public function testListShowsTheSizeOfEachBackup(): void
    {
        $result = $this->runCommand(BackupCommand::class, ['list'], environment: ['FORMWORK_TEST_BACKUPS' => '2']);

        $this->assertMatchesRegularExpression('/seeded-1\.zip.*1(\.0+)? ?KB/', $result['output']);
        $this->assertMatchesRegularExpression('/seeded-2\.zip.*2(\.0+)? ?KB/', $result['output']);
    }

    public function testBackupIsCreatedWithTheGivenName(): void
    {
        $result = $this->runCommand(BackupCommand::class, ['make', '--name=custom', '--hostname=example.test']);

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);
        $this->assertStringContainsString('Backup created:', $result['output']);
        $this->assertMatchesRegularExpression('/custom-\d{8}-\d{6}\.zip/', $result['output']);
    }

    public function testBackupNameSupportsPlaceholders(): void
    {
        $result = $this->runCommand(BackupCommand::class, ['make', '--name={{hostname}}-backup', '--hostname=example.test']);

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);
        $this->assertMatchesRegularExpression('/example-test-backup-\d{8}-\d{6}\.zip/', $result['output']);
    }

    public function testBackupUsesTheConfiguredNameByDefault(): void
    {
        $result = $this->runCommand(BackupCommand::class, ['make', '--hostname=example.test']);

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);
        $this->assertMatchesRegularExpression('/example-test-formwork-backup-\d{8}-\d{6}\.zip/', $result['output']);
    }

    public function testBackupWithoutAHostnameFallsBackToTheSystemHostname(): void
    {
        $result = $this->runCommand(BackupCommand::class, ['make']);

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);
        $this->assertStringContainsString('Creating backup', $result['output']);
        $this->assertStringContainsString('Backup created:', $result['output']);
    }

    public function testArchiveCreationFailureIsReported(): void
    {
        $result = $this->runCommand(BackupCommand::class, ['make'], environment: ['FORMWORK_TEST_BROKEN_BACKUPS' => '1']);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringNotContainsString('Backup created:', $result['output']);
        $this->assertStringNotContainsString('Fatal error', $result['error'] . $result['output']);
        $this->assertStringNotContainsString('Uncaught', $result['error'] . $result['output']);
    }

    public function testInvalidBackupNamesAreReported(): void
    {
        $result = $this->runCommand(BackupCommand::class, ['make', '--name=invalid name!']);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('invalid characters', $result['error'] . $result['output']);
    }
}
