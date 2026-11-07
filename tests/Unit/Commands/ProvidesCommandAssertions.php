<?php

namespace Formwork\Tests\Unit\Commands;

use Formwork\Cms\App;
use Formwork\Commands\CommandInterface;
use Formwork\Services\Container;
use Formwork\Tests\Unit\Commands\Fixtures\OfflineClientFixture;
use Formwork\Updater\Updater;
use Formwork\Utils\FileSystem;
use League\CLImate\CLImate;
use League\CLImate\Util\Writer\Buffer;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Runs commands over the application of the tests, capturing what they print
 *
 * @mixin TestCase
 */
trait ProvidesCommandAssertions
{
    /**
     * Run a command and check its exit code and what it prints (standard output and error together)
     *
     * Each expectation is a string that must be printed, a `/regular expression/` that must match, or a string
     * prefixed with `!` that must not be printed.
     *
     * @param class-string<CommandInterface>             $command
     * @param list<string>                               $arguments
     * @param list<string>                               $expectations
     * @param array{backups?: int, brokenBackups?: bool} $setup        Number of dummy backups to create, or `brokenBackups` to make the backup path unusable
     * @param string                                     $scenario     Release served to the updater: `uptodate`, `outdated`, `install`, `unreachable` (none by default)
     */
    protected function assertCommand(string $command, array $arguments, int $exitCode, array $expectations = [], array $setup = [], string $scenario = 'none'): void
    {
        $app = App::instance();
        $app->load();

        $this->prepareCommand($app, $setup, $scenario);

        $buffer = new Buffer();

        $instance = new $command($this->bufferedConsole($buffer));

        $code = $instance(['bin/formwork test', ...$arguments]);

        $this->assertOutput($buffer->get(), $code, $exitCode, $expectations);
    }

    /**
     * Create a console writing standard output and error, without colors, to the given buffer
     */
    protected function bufferedConsole(Buffer $buffer): CLImate
    {
        $climate = new CLImate();
        $climate->output->add('out', $buffer)->defaultTo('out');
        $climate->output->add('error', $buffer);
        $climate->forceAnsiOff();

        return $climate;
    }

    /**
     * Run `bin/formwork` in a separate process, as it terminates the script and runs the commands with their own console
     *
     * @param list<string> $arguments
     * @param list<string> $expectations
     */
    protected function assertBinary(array $arguments, int $exitCode, array $expectations = []): void
    {
        $process = new Process([(new PhpExecutableFinder())->find() ?: 'php', dirname(__DIR__, 3) . '/bin/formwork', ...$arguments]);
        $process->setTimeout(60);
        $process->run();

        $output = (string) preg_replace('/\e\[[\d;]*m/', '', $process->getOutput() . $process->getErrorOutput());

        $this->assertOutput($output, $process->getExitCode(), $exitCode, $expectations);
    }

    #[After]
    protected function removeCommandFiles(): void
    {
        $root = App::instance()->rootPath();

        foreach (['backup', 'updates.json', 'update.zip'] as $file) {
            if (FileSystem::exists("{$root}/{$file}")) {
                FileSystem::delete("{$root}/{$file}", recursive: true);
            }
        }
    }

    /**
     * @param array{backups?: int, brokenBackups?: bool} $setup
     */
    private function prepareCommand(App $app, array $setup, string $scenario): void
    {
        $path = $app->rootPath() . '/backup';

        if (($setup['backups'] ?? 0) > 0) {
            FileSystem::createDirectory($path, recursive: true);
        }

        for ($i = 1; $i <= ($setup['backups'] ?? 0); ++$i) {
            file_put_contents("{$path}/seeded-{$i}.zip", str_repeat('x', 1024 * $i));
            touch("{$path}/seeded-{$i}.zip", time() - 3600 * (10 - $i));
        }

        if ($setup['brokenBackups'] ?? false) {
            file_put_contents($path, 'not a directory');
        }

        if ($scenario === 'none') {
            return;
        }

        OfflineClientFixture::$scenario = $scenario;

        // The updater is replaced by one served by the offline client, even if it has already been resolved
        $container = $app->getService(Container::class);
        $resolved = new ReflectionProperty(Container::class, 'resolved');
        $resolved->setValue($container, array_diff_key($resolved->getValue($container), [Updater::class => true]));

        $root = $app->rootPath();

        $container->define(Updater::class, new Updater([
            ...$app->config()->getArray('system.updates'),
            'registryFile'        => $root . '/updates.json',
            'tempFile'            => $root . '/update.zip',
            'cleanupAfterInstall' => false,
        ], $root, new OfflineClientFixture()));
    }

    /**
     * @param list<string> $expectations
     */
    private function assertOutput(string $output, ?int $code, int $exitCode, array $expectations): void
    {
        $this->assertSame($exitCode, $code, $output);

        foreach ($expectations as $expectation) {
            match (true) {
                str_starts_with($expectation, '!') => $this->assertStringNotContainsString(substr($expectation, 1), $output),
                str_starts_with($expectation, '/') => $this->assertMatchesRegularExpression($expectation, $output),
                default                            => $this->assertStringContainsString($expectation, $output),
            };
        }
    }
}
