<?php

namespace Formwork\Commands;

use Formwork\Backup\Backupper;
use Formwork\Cms\App;
use Formwork\Exceptions\TranslatedException;
use Formwork\Utils\FileSystem;
use League\CLImate\CLImate;
use League\CLImate\Exceptions\InvalidArgumentException;
use RuntimeException;

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
            'name' => [
                'longPrefix'   => 'name',
                'description'  => 'Set the name for the backup, use {{hostname}}, {{site}}, {{context}}, {{version}} and {{random}} as placeholders',
                'defaultValue' => null,
                'castTo'       => 'string',
                'noValue'      => false,
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

    public function __invoke(?array $argv = null): int
    {
        $argv ??= $_SERVER['argv'] ?? [];

        $this->app = App::instance();
        $this->app->load();

        if (count($argv) < 2 || $this->climate->arguments->defined('help', $argv)) {
            $this->help($argv);
            return 0;
        }

        try {
            $this->climate->arguments->parse($argv);
        } catch (InvalidArgumentException $e) {
            $this->climate->error($e->getMessage());
            return 1;
        }

        $action = (string) $this->climate->arguments->get('action');

        if (isset($this->actions[$action])) {
            try {
                return $this->{$action}($argv);
            } catch (InvalidArgumentException $e) {
                $this->climate->error($e->getMessage());
                return 1;
            }
        }

        $this->climate->error(sprintf('Invalid action: %s.', $action));
        return 1;
    }

    /**
     * `backup make` action
     *
     * @param list<string> $argv
     */
    public function make(array $argv = []): int
    {
        $this->climate->out('Creating backup... this may take a while depending on the size of your installation.');
        /** @var ?string $name */
        $name = $this->climate->arguments->get('name') ?: null;
        /** @var string $hostname */
        $hostname = $this->climate->arguments->get('hostname') ?: (gethostname() ?: 'local-cli');
        $backupper = $this->app->getService(Backupper::class);
        try {
            $file = $backupper->backup($name, $hostname);
        } catch (RuntimeException|TranslatedException $e) {
            $this->climate->error($e->getMessage());
            return 1;
        }
        $this->climate->br()->out(sprintf('<green>Backup created:</green> %s', $file));

        return 0;
    }

    /**
     * `backup list` action
     *
     * @param list<string> $argv
     */
    public function list(array $argv = []): int
    {
        $backups = $this->app->getService(Backupper::class)->getBackups();
        if (count($backups) === 0) {
            $this->climate->green('No backups found.');

            return 0;
        }
        $this->climate->green('Available backups:');
        foreach ($backups as $backup) {
            $name = basename($backup);
            $size = FileSystem::formatSize(FileSystem::size($backup));
            $time = date('Y-m-d H:i:s T', FileSystem::lastModifiedTime($backup));

            $this->climate->out(sprintf('  <light_gray>[%s]</light_gray> %s <cyan>%s</cyan>', $time, $name, $size));
        }

        return 0;
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
