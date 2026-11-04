<?php

namespace Formwork\Tests\Unit\Commands;

use Formwork\Cms\App;
use Formwork\Commands\UpdatesCommand;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Commands\Fixtures\RunsCommands;
use Formwork\Tests\UsesApplication;
use Formwork\Updater\SemVer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpdatesCommand::class)]
#[UsesApplication]
final class UpdatesCommandTest extends TestCase
{
    use RunsCommands;

    public function testExitCodeIsPassedToTheProcess(): void
    {
        $this->assertSame(0, $this->runCommandInSubprocess(UpdatesCommand::class, ['check'], 'uptodate')['exitCode']);
        $this->assertSame(1, $this->runCommandInSubprocess(UpdatesCommand::class, ['check'], 'unreachable')['exitCode']);
    }

    public function testHelpListsTheActions(): void
    {
        $result = $this->runCommand(UpdatesCommand::class);

        $this->assertSame(0, $result['exitCode']);
        $this->assertStringContainsString('Available Actions:', $result['output']);
        $this->assertStringContainsString('check', $result['output']);
        $this->assertStringContainsString('update', $result['output']);
    }

    public function testHelpOptionShowsTheHelp(): void
    {
        $result = $this->runCommand(UpdatesCommand::class, ['check', '--help']);

        $this->assertSame(0, $result['exitCode']);
        $this->assertStringContainsString('Available Actions:', $result['output']);
    }

    public function testUnknownActionsAreRejected(): void
    {
        $result = $this->runCommand(UpdatesCommand::class, ['bogus'], 'uptodate');

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('Invalid action: bogus.', $result['error'] . $result['output']);
    }

    public function testCheckReportsWhenFormworkIsUpToDate(): void
    {
        $result = $this->runCommand(UpdatesCommand::class, ['check'], 'uptodate');

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);
        $this->assertStringContainsString('is already up to date.', $result['output']);
        $this->assertStringContainsString(App::VERSION, $result['output']);
    }

    public function testCheckReportsAvailableUpdates(): void
    {
        $result = $this->runCommand(UpdatesCommand::class, ['check'], 'outdated');

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);
        $this->assertStringContainsString(App::VERSION . ' is out of date.', $result['output']);
        $this->assertStringContainsString('Latest available version:', $result['output']);
        $this->assertStringContainsString($this->nextMinor(), $result['output']);
    }

    public function testCheckSuggestsTheCommandToUpdate(): void
    {
        $result = $this->runCommand(UpdatesCommand::class, ['check'], 'outdated');

        $this->assertStringContainsString('Run bin/formwork test update to update', $this->stripFormatting($result['output']));
    }

    public function testSuggestedCommandDoesNotDropTheCommandWhenOptionsFollowTheAction(): void
    {
        $result = $this->runCommand(UpdatesCommand::class, ['check', '--force'], 'outdated');

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);
        $this->assertStringContainsString('Run bin/formwork test update to update', $this->stripFormatting($result['output']));
        $this->assertStringNotContainsString('check update', $this->stripFormatting($result['output']));
    }

    public function testCheckFailureIsReported(): void
    {
        $result = $this->runCommand(UpdatesCommand::class, ['check'], 'unreachable');

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('Cannot check for updates:', $result['error'] . $result['output']);
    }

    public function testUpdateDoesNothingWhenFormworkIsUpToDate(): void
    {
        $result = $this->runCommand(UpdatesCommand::class, ['update', '--no-backup'], 'uptodate');

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);
        $this->assertStringContainsString('is already up to date.', $result['output']);
        $this->assertStringNotContainsString('Updating', $result['output']);
    }

    public function testUpdateFailureWhileCheckingIsReported(): void
    {
        $result = $this->runCommand(UpdatesCommand::class, ['update', '--no-backup'], 'unreachable');

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('Cannot check for updates:', $result['error'] . $result['output']);
    }

    public function testUpdateInstallsTheLatestRelease(): void
    {
        $result = $this->runCommand(UpdatesCommand::class, ['update', '--no-backup'], 'install');

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);
        $this->assertStringContainsString('has been updated successfully', $result['output']);
        $this->assertStringContainsString($this->nextMinor(), $result['output']);
    }

    public function testUpdateMakesABackupBeforeInstalling(): void
    {
        $result = $this->runCommand(UpdatesCommand::class, ['update'], 'install');

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);
        $this->assertStringContainsString('Creating backup before update', $result['output']);
        $this->assertStringContainsString('has been updated successfully', $result['output']);
    }

    public function testBackupCanBeSkippedBeforeUpdating(): void
    {
        $result = $this->runCommand(UpdatesCommand::class, ['update', '--no-backup'], 'install');

        $this->assertStringNotContainsString('Creating backup', $result['output']);
    }

    public function testInstallationFailureIsReported(): void
    {
        $result = $this->runCommand(UpdatesCommand::class, ['update', '--no-backup'], 'outdated');

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('Cannot install updates:', $result['error'] . $result['output']);
        $this->assertStringNotContainsString('has been updated successfully', $result['output']);
    }

    public function testBackupFailureBeforeUpdatingIsReported(): void
    {
        $result = $this->runCommand(UpdatesCommand::class, ['update'], 'install', ['FORMWORK_TEST_BROKEN_BACKUPS' => '1']);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringNotContainsString('Uncaught', $result['error'] . $result['output']);
        $this->assertStringNotContainsString('has been updated successfully', $result['output']);
    }

    private function nextMinor(): string
    {
        $semVer = SemVer::fromString(App::VERSION);

        return sprintf('%d.%d.0', $semVer->major(), $semVer->minor() + 1);
    }

    private function stripFormatting(string $output): string
    {
        return (string) preg_replace('/\e\[[\d;]*m/', '', $output);
    }
}
