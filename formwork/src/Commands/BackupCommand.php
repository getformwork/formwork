<?php

namespace Formwork\Commands;

use Formwork\Backup\Backupper;
use Formwork\Cms\App;
use Formwork\Utils\FileSystem;
use League\CLImate\CLImate;
use League\CLImate\Exceptions\InvalidArgumentException;

/**
 * @since 2.1.0
 */
final class BackupCommand implements CommandInterface
{
    /**
     * CLImate instance
     */
    private CLImate $climate;

    /**
     * App instance
     */
    private App $app;

    /**
     * @var array<string, array{description: string}>
     */
    private array $actions = [
        'make' => [
            'description' => 'Create a backup of the Formwork installation',
        ],
        'list' => [
            'description' => 'List available backups',
        ],
    ];

    public function __construct()
    {
        $this->climate = new CLImate();

        $this->climate->arguments->add([
            'action' => [
                'description' => 'Backup action to perform (make, list)',
                'required'    => true,
            ],
            'hostname' => [
                'longPrefix'   => 'hostname',
                'description'  => 'Set the hostname for the backup (default: current system hostname)',
                'defaultValue' => null,
                'castTo'       => 'string',
                'noValue'      => false,
            ],
            'help' => [
                'prefix'      => 'h',
                'longPrefix'  => 'help',
                'description' => 'Show this help screen',
                'noValue'     => true,
            ],
        ]);
    }

    public function __invoke(?array $argv = null): never
    {
        $argv ??= $_SERVER['argv'] ?? [];

        $this->app = App::instance();
        $this->app->load();

        if (count($argv) < 2 || $this->climate->arguments->defined('help', $argv)) {
            $this->help($argv);
            exit(0);
        }

        try {
            $this->climate->arguments->parse($argv);
        } catch (InvalidArgumentException $e) {
            $this->climate->error($e->getMessage());
            exit(1);
        }

        $action = (string) $this->climate->arguments->get('action');

        if (isset($this->actions[$action])) {
            try {
                $this->{$action}($argv);
                exit(0);
            } catch (InvalidArgumentException $e) {
                $this->climate->error($e->getMessage());
                exit(1);
            }
        }

        $this->climate->error(sprintf('Invalid action: %s.', $action));
        exit(1);
    }

    /**
     * `backup make` action
     *
     * @param list<string> $argv
     */
    public function make(array $argv = []): void
    {
        $this->climate->out('Creating backup... this may take a while depending on the size of your installation.');
        /** @var string $hostname */
        $hostname = $this->climate->arguments->get('hostname') ?: (gethostname() ?: 'local-cli');
        $file = $this->app->getService(Backupper::class)->backup(hostname: $hostname);
        $this->climate->br()->out(sprintf('<green>Backup created:</green> %s', $file));
    }

    /**
     * `backup list` action
     *
     * @param list<string> $argv
     */
    public function list(array $argv = []): void
    {
        $backups = $this->app->getService(Backupper::class)->getBackups();
        if (count($backups) === 0) {
            $this->climate->green('No backups found.');
            return;
        }
        $this->climate->green('Available backups:');
        foreach ($backups as $backup) {
            $name = basename($backup);
            $size = FileSystem::formatSize(FileSystem::size($backup));
            $time = date('Y-m-d H:i:s T', FileSystem::lastModifiedTime($backup));

            $this->climate->out(sprintf('  <light_gray>[%s]</light_gray> %s <cyan>%s</cyan>', $time, $name, $size));
        }
    }

    /**
     * @param list<string> $argv
     */
    private function help(array $argv): void
    {
        $this->climate->usage($argv);
        $this->climate->br();
        $this->climate->out('Available Actions:');

        foreach ($this->actions as $name => ['description' => $description]) {
            $this->climate->tab()->out($name);
            $this->climate->tab(2)->out($description);
        }
    }
}
