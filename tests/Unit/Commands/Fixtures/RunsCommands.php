<?php

namespace Formwork\Tests\Unit\Commands\Fixtures;

use Formwork\Cms\App;
use Formwork\Commands\BackupCommand;
use Formwork\Commands\CacheCommand;
use Formwork\Commands\FormworkCommandRunner;
use Formwork\Commands\ServeCommand;
use Formwork\Commands\UpdatesCommand;
use Formwork\Services\Container;
use Formwork\Updater\Updater;
use Formwork\Utils\FileSystem;
use League\CLImate\CLImate;
use League\CLImate\Util\Writer\Buffer;
use ReflectionMethod;

/**
 * Runs commands in the same process, capturing their output
 *
 * Commands that use the application must run over the application of the tests (`UsesApplication`)
 */
trait RunsCommands
{
    /**
     * Run a command and return its exit code and output
     *
     * @param class-string<BackupCommand|CacheCommand|FormworkCommandRunner|ServeCommand|UpdatesCommand> $command
     * @param list<string>                                                                               $arguments
     * @param array<string, string>                                                                      $environment `FORMWORK_TEST_BACKUPS` is the number of dummy backups to create, `FORMWORK_TEST_BROKEN_BACKUPS` makes the backup path unusable
     *
     * @return array{exitCode: int, output: string, error: string}
     */
    protected function runCommand(string $command, array $arguments = [], string $scenario = 'none', array $environment = []): array
    {
        $output = new Buffer();
        $error = new Buffer();

        $makeClimate = static function () use ($output, $error): CLImate {
            $climate = new CLImate();
            $climate->output->add('out', $output)->defaultTo('out');
            $climate->output->add('error', $error);
            $climate->forceAnsiOff();

            return $climate;
        };

        if ($scenario !== 'none') {
            OfflineClient::$scenario = $scenario;

            $container = App::instance()->getService(Container::class);

            // The updater can be replaced until it is used, then it is reused by the following runs
            if (!$container->isResolved(Updater::class)) {
                $container->define(Updater::class, OfflineClient::updater(App::instance()->rootPath()));
            }
        }

        $this->prepareCommandEnvironment($environment);

        try {
            $instance = $command === FormworkCommandRunner::class
                ? new FormworkCommandRunner($makeClimate(), static fn(string $class) => new $class($makeClimate()))
                : new $command($makeClimate());

            $exitCode = $instance->run(['bin/formwork test', ...$arguments]);
        } finally {
            $this->cleanCommandEnvironment();
        }

        return ['exitCode' => $exitCode, 'output' => $output->get(), 'error' => $error->get()];
    }

    /**
     * @param array<string, string> $environment
     */
    private function prepareCommandEnvironment(array $environment): void
    {
        $path = App::instance()->rootPath() . '/backup';

        $backups = (int) ($environment['FORMWORK_TEST_BACKUPS'] ?? 0);

        if ($backups > 0) {
            FileSystem::createDirectory($path, recursive: true);
        }

        for ($i = 1; $i <= $backups; ++$i) {
            FileSystem::write("{$path}/seeded-{$i}.zip", str_repeat('x', 1024 * $i));
            touch("{$path}/seeded-{$i}.zip", time() - 3600 * (10 - $i));
        }

        if (($environment['FORMWORK_TEST_BROKEN_BACKUPS'] ?? '') === '1') {
            FileSystem::write($path, 'not a directory');
        }
    }

    /**
     * Remove what the commands leave in the application of the tests
     */
    private function cleanCommandEnvironment(): void
    {
        $app = App::instance();
        $root = $app->rootPath();

        foreach (['/backup', '/updated.txt', '/update.zip'] as $file) {
            if (FileSystem::isDirectory($root . $file, assertExists: false)) {
                FileSystem::delete($root . $file, recursive: true);
            } elseif (FileSystem::exists($root . $file)) {
                FileSystem::delete($root . $file);
            }
        }

        $container = $app->getService(Container::class);

        // Installing a release changes the registry, the following runs must start from a clean one
        if ($container->isResolved(Updater::class)) {
            $updater = $container->get(Updater::class);
            (new ReflectionMethod($updater, 'initializeRegistry'))->invoke($updater);
        }
    }
}
