<?php

namespace Formwork\Tests\Unit\Commands;

use Formwork\Cms\App;
use Formwork\Commands\CommandInterface;
use Formwork\Commands\FormworkCommandRunner;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Commands\Fixtures\RunsCommands;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(FormworkCommandRunner::class)]
final class FormworkCommandRunnerTest extends TestCase
{
    use RunsCommands;

    public function testRunnerIsACommand(): void
    {
        $this->assertInstanceOf(CommandInterface::class, new FormworkCommandRunner());
    }

    /**
     * @param list<string> $arguments
     */
    #[DataProvider('helpArgumentsProvider')]
    public function testHelpListsTheAvailableCommands(array $arguments): void
    {
        $result = $this->runCommand(FormworkCommandRunner::class, $arguments);

        $this->assertSame(0, $result['exitCode']);
        $this->assertStringContainsString('Available Commands:', $result['output']);

        foreach (['backup', 'cache', 'serve', 'updates'] as $command) {
            $this->assertStringContainsString($command, $result['output']);
        }
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function helpArgumentsProvider(): iterable
    {
        yield 'no arguments' => [[]];
        yield 'long option' => [['--help']];
        yield 'short option' => [['-h']];
    }

    /**
     * @param list<string> $arguments
     */
    #[DataProvider('versionArgumentsProvider')]
    public function testVersionIsPrinted(array $arguments): void
    {
        $result = $this->runCommand(FormworkCommandRunner::class, $arguments);

        $this->assertSame(0, $result['exitCode']);
        $this->assertStringContainsString(App::VERSION, $result['output']);
        $this->assertStringNotContainsString('Available Commands:', $result['output']);
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function versionArgumentsProvider(): iterable
    {
        yield 'long option' => [['--version']];
        yield 'short option' => [['-v']];
    }

    public function testUnknownCommandsAreRejected(): void
    {
        $result = $this->runCommand(FormworkCommandRunner::class, ['unknown']);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('Invalid command: unknown.', $result['error'] . $result['output']);
    }

    public function testCommandsAreDispatchedWithTheirArguments(): void
    {
        $result = $this->runCommand(FormworkCommandRunner::class, ['cache', 'bogus']);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('Invalid action: bogus.', $result['error'] . $result['output']);
    }

    public function testCommandOptionsAreNotRejectedByTheRunner(): void
    {
        $result = $this->runCommand(FormworkCommandRunner::class, ['backup', 'list', '--hostname=example.test']);

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);
    }

    public function testHelpOfADispatchedCommandIsShownByTheCommand(): void
    {
        $result = $this->runCommand(FormworkCommandRunner::class, ['backup', '--help']);

        $this->assertSame(0, $result['exitCode']);
        $this->assertStringContainsString('Available Actions:', $result['output']);
        $this->assertStringContainsString('make', $result['output']);
    }

    public function testDispatchedCommandsReceiveTheCommandNameInTheUsage(): void
    {
        $result = $this->runCommand(FormworkCommandRunner::class, ['cache', '--help']);

        $this->assertStringContainsString('cache', $result['output']);
        $this->assertStringContainsString('Available Actions:', $result['output']);
    }
}
