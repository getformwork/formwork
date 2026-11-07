<?php

namespace Formwork\Commands;

use Formwork\Cache\FilesCache;
use Formwork\Cms\App;
use Formwork\Utils\FileSystem;
use League\CLImate\CLImate;
use League\CLImate\Exceptions\InvalidArgumentException;

/**
 * @since 2.1.0
 */
final class CacheCommand implements CommandInterface
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
        'clear' => [
            'description' => 'Clear cache files from the specified cache type',
        ],
        'invalidate' => [
            'description' => 'Invalidate cache for the specified cache type (does not delete files)',
        ],
        'stats' => [
            'description' => 'Show cache statistics for the specified cache type',
        ],
    ];

    public function __construct()
    {
        $this->climate = new CLImate();

        $this->climate->arguments->add([
            'action' => [
                'description' => 'Cache action to perform (clear, invalidate, stats)',
                'required'    => true,
            ],
            'type' => [
                'description'  => 'Type of cache to act on (all, config, images, pages)',
                'required'     => true,
                'defaultValue' => 'all',
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
        $type = (string) $this->climate->arguments->get('type');

        if (isset($this->actions[$action])) {
            try {
                $this->{$action}($type, $argv);
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
     * `cache clear` action
     *
     * @param list<string> $argv
     */
    public function clear(string $type, array $argv = []): void
    {
        switch ($type) {
            case 'all':
                $this->clearConfigCache();
                $this->clearImagesCache();
                $this->clearPagesCache();
                $this->climate->green('All caches cleared.');
                break;
            case 'config':
                $this->clearConfigCache();
                $this->climate->green('Config cache cleared.');
                break;
            case 'pages':
                $this->clearPagesCache();
                $this->climate->green('Pages cache cleared.');
                break;
            case 'images':
                $this->clearImagesCache();
                $this->climate->green('Images cache cleared.');
                break;
            default:
                throw new InvalidArgumentException('Invalid cache type for clearing.');
        }
    }

    /**
     * `cache invalidate` action
     *
     * @param list<string> $argv
     */
    public function invalidate(string $type, array $argv = []): void
    {
        switch ($type) {
            case 'all':
                $this->invalidatePagesCache();
                $this->climate->green('All caches invalidated.');
                break;

            case 'pages':
                $this->invalidatePagesCache();
                $this->climate->green('Pages cache invalidated.');
                break;

            default:
                throw new InvalidArgumentException('Invalid cache type for invalidation.');
        }
    }

    /**
     * `cache stats` action
     *
     * @param list<string> $argv
     */
    public function stats(string $type, array $argv = []): void
    {
        switch ($type) {
            case 'all':
                $this->configCacheStats();
                $this->climate->br();
                $this->imagesCacheStats();
                $this->climate->br();
                $this->pagesCacheStats();
                $this->climate->br();
                break;
            case 'config':
                $this->configCacheStats();
                break;
            case 'images':
                $this->imagesCacheStats();
                break;
            case 'pages':
                $this->pagesCacheStats();
                break;
            default:
                throw new InvalidArgumentException('Invalid cache type for stats.');
        }
    }

    /**
     * @param list<string>|null $argv
     */
    private function help(?array $argv = null): void
    {
        $this->climate->usage($argv);
        $this->climate->br();
        $this->climate->out('Available Actions:');

        foreach ($this->actions as $name => ['description' => $description]) {
            $this->climate->tab()->out($name);
            $this->climate->tab(2)->out($description);
        }
    }

    /**
     * Output config cache stats
     */
    private function configCacheStats(): void
    {
        $path = ROOT_PATH . '/cache/config/';
        $items = iterator_to_array(FileSystem::listContents($path));
        $size = FileSystem::directorySize($path);

        $this->climate->out('<green>Config Cache</green>');
        $this->climate->out(sprintf('  Items number: <cyan>%d</cyan>', count($items)));
        $this->climate->out(sprintf('  Total size:   <cyan>%s</cyan>', FileSystem::formatSize($size)));
    }

    /**
     * Output images cache stats
     */
    private function imagesCacheStats(): void
    {
        $path = $this->app->config()->get('system.images.processPath');
        $exists = FileSystem::isDirectory($path, assertExists: false);
        $items = $exists ? iterator_to_array(FileSystem::listContents($path)) : [];
        $size = $exists ? FileSystem::directorySize($path) : 0;

        $this->climate->out('<green>Images Cache</green>');
        $this->climate->out(sprintf('  Items number: <cyan>%d</cyan>', count($items)));
        $this->climate->out(sprintf('  Total size:   <cyan>%s</cyan>', FileSystem::formatSize($size)));
    }

    /**
     * Output pages cache stats
     */
    private function pagesCacheStats(): void
    {
        $path = $this->app->config()->get('system.cache.path');
        $items = iterator_to_array(FileSystem::listContents($path));
        $size = FileSystem::directorySize($path);

        $this->climate->out('<green>Pages Cache</green>');
        $this->climate->out(sprintf('  Items number: <cyan>%d</cyan>', count($items)));
        $this->climate->out(sprintf('  Total size:   <cyan>%s</cyan>', FileSystem::formatSize($size)));
    }

    /**
     * Clear config cache
     */
    private function clearConfigCache(): void
    {
        $path = ROOT_PATH . '/cache/config/';
        FileSystem::delete($path, recursive: true);
        FileSystem::createDirectory($path, recursive: true);
    }

    /**
     * Clear images cache
     */
    private function clearImagesCache(): void
    {
        $path = $this->app->config()->get('system.images.processPath');

        if (FileSystem::exists($path)) {
            FileSystem::delete($path, recursive: true);
        }

        FileSystem::createDirectory($path, recursive: true);
    }

    /**
     * Clear pages cache
     */
    private function clearPagesCache(): void
    {
        /** @var FilesCache */
        $cache = $this->app->getService('cache');

        $cache->clear();

        $site = $this->app->site();
        if ($site->contentPath() !== null) {
            FileSystem::touch($site->contentPath());
        }
    }

    /**
     * Invalidate pages cache
     */
    private function invalidatePagesCache(): void
    {
        $site = $this->app->site();
        if ($site->contentPath() !== null) {
            FileSystem::touch($site->contentPath());
        }
    }
}
