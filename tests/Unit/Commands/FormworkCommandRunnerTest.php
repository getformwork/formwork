<?php

namespace Formwork\Tests\Unit\Commands;

use Formwork\Cms\App;
use Formwork\Commands\FormworkCommandRunner;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(FormworkCommandRunner::class)]
final class FormworkCommandRunnerTest extends TestCase
{
    use ProvidesCommandAssertions;

    /**
     * @param list<string> $arguments
     * @param list<string> $expectations
     */
    #[DataProvider('commandResultProvider')]
    public function testCommandExitsWithTheExpectedResult(array $arguments, int $exitCode, array $expectations): void
    {
        $this->assertBinary($arguments, $exitCode, $expectations);
    }

    /**
     * @return iterable<string, array{list<string>, int, list<string>}>
     */
    public static function commandResultProvider(): iterable
    {
        $commands = ['Available Commands:', 'backup', 'cache', 'serve', 'updates'];

        yield 'help without arguments' => [[], 0, $commands];
        yield 'help option' => [['--help'], 0, $commands];
        yield 'short help option' => [['-h'], 0, $commands];
        yield 'version option' => [['--version'], 0, [App::VERSION, '!Available Commands:']];
        yield 'short version option' => [['-v'], 0, [App::VERSION, '!Available Commands:']];
        yield 'unknown command' => [['unknown'], 1, ['Invalid command: unknown.']];
        yield 'command arguments are passed to the command' => [['cache', 'bogus'], 1, ['Invalid action: bogus.']];
        yield 'command options are not rejected' => [['backup', 'list', '--hostname=example.test'], 0, []];
        yield 'help of a command' => [['backup', '--help'], 0, ['Available Actions:', 'make']];
        yield 'usage of a command shows its name' => [['cache', '--help'], 0, ['cache', 'Available Actions:']];
    }
}
