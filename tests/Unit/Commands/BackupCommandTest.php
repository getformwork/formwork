<?php

namespace Formwork\Tests\Unit\Commands;

use Formwork\Commands\BackupCommand;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(BackupCommand::class)]
#[UsesApplication]
final class BackupCommandTest extends TestCase
{
    use ProvidesCommandAssertions;

    /**
     * @param list<string>                               $arguments
     * @param list<string>                               $expectations
     * @param array{backups?: int, brokenBackups?: bool} $setup
     */
    #[DataProvider('commandResultProvider')]
    public function testCommandExitsWithTheExpectedResult(array $arguments, int $exitCode, array $expectations, array $setup = []): void
    {
        $this->assertCommand(BackupCommand::class, $arguments, $exitCode, $expectations, $setup);
    }

    /**
     * @return iterable<string, array{list<string>, int, list<string>, 3?: array{backups?: int, brokenBackups?: bool}}>
     */
    public static function commandResultProvider(): iterable
    {
        yield 'help without arguments' => [[], 0, ['Available Actions:', 'make', 'list']];
        yield 'help option' => [['make', '--help'], 0, ['Available Actions:']];
        yield 'unknown action' => [['bogus'], 1, ['Invalid action: bogus.']];
        yield 'list without backups' => [['list'], 0, ['No backups found.']];
        yield 'list shows the sizes' => [['list'], 0, ['Available backups:', '/seeded-1\.zip.*1(\.0+)? ?KB/', '/seeded-2\.zip.*2(\.0+)? ?KB/'], ['backups' => 2]];
        yield 'make with a name' => [['make', '--name=custom', '--hostname=example.test'], 0, ['Backup created:', '/custom-\d{8}-\d{6}\.zip/']];
        yield 'make with placeholders in the name' => [['make', '--name={{hostname}}-backup', '--hostname=example.test'], 0, ['/example-test-backup-\d{8}-\d{6}\.zip/']];
        yield 'make with the configured name' => [['make', '--hostname=example.test'], 0, ['/example-test-formwork-backup-\d{8}-\d{6}\.zip/']];
        yield 'make with the system hostname' => [['make'], 0, ['Creating backup', 'Backup created:']];
        yield 'make with an invalid name' => [['make', '--name=invalid name!'], 1, ['invalid characters']];
    }

    public function testListShowsTheBackupsFromTheNewest(): void
    {
        $this->assertCommand(BackupCommand::class, ['list'], 0, ['/seeded-3\.zip.*seeded-2\.zip.*seeded-1\.zip/s'], ['backups' => 3]);
    }

    public function testArchiveCreationFailureIsReported(): void
    {
        $this->assertCommand(BackupCommand::class, ['make'], 1, ['!Backup created:', '!Fatal error', '!Uncaught'], ['brokenBackups' => true]);
    }
}
