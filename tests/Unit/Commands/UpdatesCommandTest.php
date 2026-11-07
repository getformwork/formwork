<?php

namespace Formwork\Tests\Unit\Commands;

use Formwork\Cms\App;
use Formwork\Commands\UpdatesCommand;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use Formwork\Updater\SemVer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(UpdatesCommand::class)]
#[UsesApplication]
final class UpdatesCommandTest extends TestCase
{
    use ProvidesCommandAssertions;

    /**
     * @param list<string>                               $arguments
     * @param list<string>                               $expectations
     * @param array{backups?: int, brokenBackups?: bool} $setup
     */
    #[DataProvider('commandResultProvider')]
    public function testCommandExitsWithTheExpectedResult(array $arguments, string $scenario, int $exitCode, array $expectations, array $setup = []): void
    {
        $this->assertCommand(UpdatesCommand::class, $arguments, $exitCode, $expectations, $setup, $scenario);
    }

    /**
     * @return iterable<string, array{list<string>, string, int, list<string>, 4?: array{backups?: int, brokenBackups?: bool}}>
     */
    public static function commandResultProvider(): iterable
    {
        $current = App::VERSION;
        $next = sprintf('%d.%d.0', SemVer::fromString($current)->major(), SemVer::fromString($current)->minor() + 1);

        yield 'help without arguments' => [[], 'none', 0, ['Available Actions:', 'check', 'update']];
        yield 'help option' => [['--help'], 'none', 0, ['Available Actions:']];
        yield 'unknown action' => [['bogus'], 'none', 1, ['Invalid action: bogus.']];
        yield 'check when up to date' => [['check'], 'uptodate', 0, ['is already up to date.', $current]];
        yield 'check with an available update' => [['check'], 'outdated', 0, ["$current is out of date.", 'Latest available version:', $next]];
        yield 'check failing' => [['check'], 'unreachable', 1, ['Cannot check for updates:']];
        yield 'update when up to date' => [['update', '--no-backup'], 'uptodate', 0, ['is already up to date.', '!Updating']];
        yield 'update failing while checking' => [['update', '--no-backup'], 'unreachable', 1, ['Cannot check for updates:']];
        yield 'update' => [['update', '--no-backup'], 'install', 0, ['has been updated successfully', $next, '!Creating backup']];
        yield 'update with a backup' => [['update'], 'install', 0, ['Creating backup before update', 'has been updated successfully']];
        yield 'update failing while installing' => [['update', '--no-backup'], 'outdated', 1, ['Cannot install updates:', '!has been updated successfully']];
    }

    /**
     * @param list<string> $arguments
     */
    #[DataProvider('suggestionProvider')]
    public function testCheckSuggestsTheCommandToUpdate(array $arguments): void
    {
        $this->assertCommand(UpdatesCommand::class, $arguments, 0, ['Run bin/formwork test update to update', '!check update'], scenario: 'outdated');
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function suggestionProvider(): iterable
    {
        yield 'check' => [['check']];
        yield 'check with options' => [['check', '--force']];
    }

    public function testBackupFailureBeforeUpdatingIsReported(): void
    {
        $this->assertCommand(UpdatesCommand::class, ['update'], 1, ['!Uncaught', '!has been updated successfully'], ['brokenBackups' => true], 'install');
    }
}
