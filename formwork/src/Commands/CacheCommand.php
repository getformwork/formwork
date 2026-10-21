<?php

namespace Formwork\Commands;

use Formwork\Cache\CacheManager;
use Formwork\Cache\CountableCache;
use Formwork\Cache\FilesCache;
use Formwork\Cms\App;
use Formwork\Services\Loaders\ConfigServiceLoader;
use Formwork\Utils\FileSystem;
use League\CLImate\CLImate;
use League\CLImate\Exceptions\InvalidArgumentException;
use Psr\Log\LoggerInterface;

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

    private ?LoggerInterface $logger = null;

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
                'description'  => 'Type of cache to act on (all, config, images, or the name of a cache like pages)',
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

        if ($this->app->hasService(LoggerInterface::class)) {
            $this->logger = $this->app->getService(LoggerInterface::class);
        }

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
                $this->clearCaches([...array_keys($this->cacheManager()->getAll()), 'images', 'config']);
                $this->climate->green('All caches cleared.');
                break;
            case 'config':
                $this->clearCaches(['config']);
                $this->climate->green('Config cache cleared.');
                break;
            case 'images':
                $this->clearCaches(['images']);
                $this->climate->green('Images cache cleared.');
                break;
            default:
                if ($type === '' || !$this->cacheManager()->has($type)) {
                    throw new InvalidArgumentException('Invalid cache type for clearing.');
                }
                $this->clearCaches([$type]);
                $this->climate->green(sprintf('%s cache cleared.', ucfirst($type)));
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
                foreach (array_keys($this->cacheManager()->getAll()) as $name) {
                    $this->managedCacheStats((string) $name);
                    $this->climate->br();
                }
                break;
            case 'config':
                $this->configCacheStats();
                break;
            case 'images':
                $this->imagesCacheStats();
                break;
            default:
                if ($type === '' || !$this->cacheManager()->has($type)) {
                    throw new InvalidArgumentException('Invalid cache type for stats.');
                }
                $this->managedCacheStats($type);
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
        $path = $this->app->rootPath() . '/cache/config/';
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
        $path = $this->app->config()->getString('system.images.processPath');
        $items = iterator_to_array(FileSystem::listContents($path));
        $size = FileSystem::directorySize($path);

        $this->climate->out('<green>Images Cache</green>');
        $this->climate->out(sprintf('  Items number: <cyan>%d</cyan>', count($items)));
        $this->climate->out(sprintf('  Total size:   <cyan>%s</cyan>', FileSystem::formatSize($size)));
    }

    /**
     * Output stats of a cache of the cache manager
     *
     * @param non-empty-string $name
     */
    private function managedCacheStats(string $name): void
    {
        $cache = $this->cacheManager()->get($name);

        $this->climate->out(sprintf('<green>%s Cache</green>', ucfirst($name)));
        $this->climate->out(sprintf('  Items number: <cyan>%d</cyan>', $cache instanceof CountableCache ? count($cache) : 0));

        if ($cache instanceof FilesCache) {
            $size = FileSystem::exists($path = $cache->path()) ? FileSystem::directorySize($path) : 0;
            $this->climate->out(sprintf('  Total size:   <cyan>%s</cyan>', FileSystem::formatSize($size)));
        }
    }

    /**
     * Clear specified caches
     *
     * @param array<non-empty-string> $types
     */
    private function clearCaches(array $types): void
    {
        foreach ($types as $type) {
            switch ($type) {
                case 'images':
                    $this->clearImagesCache();
                    break;
                case 'config':
                    $this->clearConfigCache();
                    break;
                default:
                    if ($this->cacheManager()->has($type)) {
                        $this->clearManagedCache($type);
                    }
                    break;
            }
        }

        $this->logger?->notice('Cache cleared ({types}) from CLI', ['types' => implode(', ', $types)]);
    }

    /**
     * Clear images cache
     */
    private function clearImagesCache(): void
    {
        $path = $this->app->config()->getString('system.images.processPath');
        FileSystem::delete($path, recursive: true);
        FileSystem::createDirectory($path, recursive: true);
    }

    /**
     * Clear a cache of the cache manager
     *
     * @param non-empty-string $name
     */
    private function clearManagedCache(string $name): void
    {
        $this->cacheManager()->get($name)->clear();

        if ($name === 'pages') {
            $site = $this->app->site();
            if ($site->contentPath() !== null) {
                FileSystem::touch($site->contentPath());
            }
        }
    }

    /**
     * Get the cache manager
     */
    private function cacheManager(): CacheManager
    {
        /** @var CacheManager */
        return $this->app->getService(CacheManager::class);
    }

    /**
     * Clear config cache
     */
    private function clearConfigCache(): void
    {
        ConfigServiceLoader::clearCache($this->app->rootPath());
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
