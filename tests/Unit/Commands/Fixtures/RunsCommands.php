<?php

namespace Formwork\Tests\Unit\Commands\Fixtures;

use Formwork\Commands\CommandInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Runs commands in a separate process, over a temporary installation
 */
trait RunsCommands
{
    /**
     * @param class-string<CommandInterface> $command
     * @param list<string>                   $arguments
     * @param array<string, string>          $environment
     *
     * @return array{exitCode: int, output: string, error: string}
     */
    protected function runCommand(string $command, array $arguments = [], string $scenario = 'none', array $environment = []): array
    {
        $process = new Process([
            (new PhpExecutableFinder())->find() ?: 'php',
            __DIR__ . '/run-command.php',
            $command,
            $scenario,
            '--',
            ...$arguments,
        ], null, $environment);

        $process->setTimeout(60);
        $process->run();

        return [
            'exitCode' => (int) $process->getExitCode(),
            'output'   => $process->getOutput(),
            'error'    => $process->getErrorOutput(),
        ];
    }
}
