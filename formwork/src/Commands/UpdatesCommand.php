<?php

namespace Formwork\Commands;

use Formwork\Backup\Backupper;
use Formwork\Cache\AbstractCache;
use Formwork\Cms\App;
use Formwork\Updater\Updater;
use League\CLImate\CLImate;
use League\CLImate\Exceptions\InvalidArgumentException;
use RuntimeException;

/**
 * @since 2.1.0
 */
final class UpdatesCommand implements CommandInterface
{
    /**
     * CLImate instance
     */
    private CLImate $climate;

    private App $app;

    /**
     * @var array<string, array{description: string}>
     */
    private array $actions = [
        'check' => [
            'description' => 'Check for available updates',
        ],
        'update' => [
            'description' => 'Update Formwork to the latest version',
        ],
    ];

    public function __construct()
    {
        $this->climate = new CLImate();

        $this->climate->arguments->add([
            'action' => [
                'description' => 'Updates action to perform (check, update)',
                'required'    => true,
            ],
            'force' => [
                'prefix'      => 'f',
                'longPrefix'  => 'force',
                'description' => 'Force the update retrieval, ignoring any cached data',
                'noValue'     => true,
            ],
            'no-backup' => [
                'longPrefix'  => 'no-backup',
                'description' => 'Skip the backup before updating',
                'noValue'     => true,
            ],
            'no-prefer-dist' => [
                'longPrefix'  => 'no-prefer-dist',
                'description' => 'Do not prefer dist packages when updating',
                'noValue'     => true,
            ],
            'no-cleanup' => [
                'longPrefix'  => 'no-cleanup',
                'description' => 'Do not remove temporary files after updating',
                'noValue'     => true,
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
     * `updates check` action
     *
     * @param list<string> $argv
     */
    public function check(array $argv = []): void
    {
        $force = $this->climate->arguments->defined('force');

        $updater = $this->getUpdater(['force' => $force]);

        try {
            $upToDate = $updater->checkUpdates();
        } catch (RuntimeException $e) {
            $this->climate->error("Cannot check for updates: {$e->getMessage()}");
            exit(1);
        }

        $release = $updater->latestRelease();

        if ($release === null) {
            $this->climate->error('Cannot retrieve latest release information.');
            exit(1);
        }

        if ($upToDate) {
            $this->climate->out("<bold>Formwork <cyan>{$release['tag']}</cyan></bold> is already up to date.");
            exit(0);
        }

        $currentVersion = App::VERSION;
        $this->climate->out("Formwork <bold><yellow>{$currentVersion}</yellow></bold> is out of date.");
        $this->climate->out("Latest available version: <bold><green>{$release['tag']}</green></bold>.");
        $this->climate->br();

        $command = implode(' ', array_slice($argv, 0, -1)) . ' update';
        $this->climate->out("Run <cyan>{$command}</cyan> to update to the latest version.");
    }

    /**
     * `updates update` action
     *
     * @param list<string> $argv
     */
    public function update(array $argv = []): void
    {
        $force = $this->climate->arguments->defined('force');
        $backup = !$this->climate->arguments->defined('no-backup');
        $preferDist = !$this->climate->arguments->defined('no-prefer-dist');
        $cleanup = !$this->climate->arguments->defined('no-cleanup');
        $updater = $this->getUpdater([
            'force'               => $force,
            'preferDistAssets'    => $preferDist,
            'cleanupAfterInstall' => $cleanup,
        ]);

        try {
            $upToDate = $updater->checkUpdates();
        } catch (RuntimeException $e) {
            $this->climate->error("Cannot check for updates: {$e->getMessage()}");
            exit(1);
        }

        $release = $updater->latestRelease();
        if ($release === null) {
            $this->climate->error('Cannot retrieve latest release information.');
            exit(1);
        }

        if ($upToDate) {
            $this->climate->out("<bold>Formwork <cyan>{$release['tag']}</cyan></bold> is already up to date.");
            exit(0);
        }

        if ($backup) {
            $this->climate->out('Creating backup before update... this may take a while depending on the size of your installation and site.');
            $backupper = $this->getBackupper();
            try {
                $backupper->backup();
            } catch (RuntimeException $e) {
                $this->climate->error("Cannot make backup: {$e->getMessage()}");
                exit(1);
            }
            $this->climate->br();
        }

        try {
            $this->climate->out("Updating <bold>Formwork</bold> to <bold><green>{$release['tag']}</green></bold>...");
            $updater->update();
        } catch (RuntimeException $e) {
            $this->climate->error("Cannot install updates: {$e->getMessage()}");
            exit(1);
        }
        $this->climate->br();

        if ($this->app->config()->get('system.cache.enabled')) {
            $this->climate->out('Clearing cache...');
            $this->app->getService(AbstractCache::class)->clear();
            $this->climate->br();
        }

        $this->climate->out("<bold>Formwork</bold> has been updated successfully to <bold><green>{$release['tag']}</green></bold>.");
    }

    /**
     * Get Updater instance
     *
     * @param array<string, mixed> $config
     */
    private function getUpdater(array $config): Updater
    {
        return new Updater([...$this->app->config()->get('system.updates'), ...$config], App::instance());
    }

    /**
     * Get Backupper instance
     */
    private function getBackupper(): Backupper
    {
        return new Backupper([...$this->app->config()->get('system.backup'), 'hostname' => gethostname() ?: 'local-cli']);
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
