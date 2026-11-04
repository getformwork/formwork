<?php

namespace Formwork\Commands;

use Formwork\Cms\App;
use League\CLImate\CLImate;
use League\CLImate\Exceptions\InvalidArgumentException;

/**
 * @since 2.1.0
 */
final class FormworkCommandRunner implements CommandInterface
{
    /**
     * CLImate instance
     */
    private CLImate $climate;

    /**
     * @var array<string, array{description: string, command: class-string<BackupCommand|CacheCommand|ServeCommand|UpdatesCommand>}>
     */
    private array $commands = [
        'backup' => [
            'description' => 'Create a backup of the Formwork installation',
            'command'     => BackupCommand::class,
        ],
        'cache' => [
            'description' => 'Manage Formwork cache',
            'command'     => CacheCommand::class,
        ],
        'serve' => [
            'description' => 'Start Formwork development server',
            'command'     => ServeCommand::class,
        ],
        'updates' => [
            'description' => 'Manage Formwork updates',
            'command'     => UpdatesCommand::class,
        ],
    ];

    /**
     * @param CLImate|null $climate CLImate instance, a new one is created by default
     */
    public function __construct(?CLImate $climate = null)
    {
        $this->climate = $climate ?? new CLImate();

        $this->climate->description(sprintf('<bold>Formwork <cyan>%s</cyan></bold> CLI', App::VERSION));

        $this->climate->arguments->add([
            'command' => [
                'description' => 'Command to run',
                'required'    => true,
            ],
            'arguments' => [
                'description' => 'Arguments to pass to the command',
            ],
            'help' => [
                'prefix'      => 'h',
                'longPrefix'  => 'help',
                'description' => 'Show this help screen',
                'noValue'     => true,
            ],
            'version' => [
                'prefix'      => 'v',
                'longPrefix'  => 'version',
                'description' => 'Show Formwork version',
                'noValue'     => true,
            ],
        ]);
    }

    public function __invoke(?array $argv = null): never
    {
        exit($this->run($argv ?? $_SERVER['argv'] ?? []));
    }

    /**
     * Run the command and return its exit code
     *
     * @param list<string> $argv Command line arguments
     *
     * @since 2.4.0
     */
    public function run(array $argv): int
    {
        if (count($argv) < 2 || $this->hasDirectArgument('help', $argv)) {
            $this->help($argv);
            return 0;
        }

        if ($this->hasDirectArgument('version', $argv)) {
            $this->climate->out(sprintf('<bold>Formwork <cyan>%s</cyan></bold>', App::VERSION));
            return 0;
        }

        try {
            $this->climate->arguments->parse($argv);
        } catch (InvalidArgumentException $e) {
            $this->climate->error($e->getMessage());
            return 1;
        }

        $command = (string) $this->climate->arguments->get('command');

        if (isset($this->commands[$command])) {
            try {
                $commandClass = $this->commands[$command]['command'];

                /** @var list<string> */
                $arguments = [sprintf('%s %s', $argv[0], $command), ...array_slice($argv, 2)];

                return (new $commandClass())->run($arguments);
            } catch (InvalidArgumentException $e) {
                $this->climate->error($e->getMessage());
                return 1;
            }
        }

        $this->climate->error(sprintf('Invalid command: %s.', $command));
        return 1;
    }

    /**
     * @param list<string> $argv
     */
    public function help(array $argv): void
    {
        $this->climate->usage($argv);
        $this->climate->br();
        $this->climate->out('Available Commands:');

        foreach ($this->commands as $name => ['description' => $description]) {
            $this->climate->tab()->out($name);
            $this->climate->tab(2)->out($description);
        }
    }

    /**
     * @param list<string> $argv
     */
    private function hasDirectArgument(string $name, array $argv): bool
    {
        return count($argv) === 2 && $this->climate->arguments->defined($name, $argv);
    }
}
