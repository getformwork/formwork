<?php

namespace Formwork\Tests\Unit\Commands;

use Formwork\Cache\CacheManager;
use Formwork\Cms\App;
use Formwork\Commands\CacheCommand;
use Formwork\Services\Loaders\ConfigServiceLoader;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\SimpleCache\CacheInterface;

#[CoversClass(CacheCommand::class)]
#[UsesApplication]
final class CacheCommandTest extends TestCase
{
    use ProvidesCommandAssertions;

    /**
     * @param list<string> $arguments
     * @param list<string> $expectations
     */
    #[DataProvider('commandResultProvider')]
    public function testCommandExitsWithTheExpectedResult(array $arguments, int $exitCode, array $expectations): void
    {
        $this->assertCommand(CacheCommand::class, $arguments, $exitCode, $expectations);
    }

    /**
     * @return iterable<string, array{list<string>, int, list<string>}>
     */
    public static function commandResultProvider(): iterable
    {
        yield 'help without arguments' => [[], 0, ['clear', 'invalidate', 'stats']];
        yield 'help option' => [['--help'], 0, ['Available Actions:']];
        yield 'unknown action' => [['bogus', 'all'], 1, ['Invalid action: bogus.']];
        yield 'missing action' => [['--'], 1, ['The following arguments are required: [action].']];
        yield 'clear of an unknown type' => [['clear', 'bogus'], 1, ['Invalid cache type for clearing.']];
        yield 'invalidate of an unknown type' => [['invalidate', 'bogus'], 1, ['Invalid cache type for invalidation.']];
        yield 'stats of an unknown type' => [['stats', 'bogus'], 1, ['Invalid cache type for stats.']];

        foreach (['config' => 'Config', 'pages' => 'Pages'] as $type => $title) {
            yield "clear $type" => [['clear', $type], 0, ["$title cache cleared."]];
        }

        foreach (['all' => 'All caches', 'pages' => 'Pages cache'] as $type => $message) {
            yield "invalidate $type" => [['invalidate', $type], 0, ["$message invalidated."]];
        }

        // Only the cache of the pages can be invalidated
        foreach (['config', 'images'] as $type) {
            yield "invalidate $type" => [['invalidate', $type], 1, ['Invalid cache type for invalidation.']];
        }

        foreach (['config' => 'Config', 'pages' => 'Pages'] as $type => $title) {
            yield "stats $type" => [['stats', $type], 0, ["$title Cache", 'Items number:']];
        }
    }

    /**
     * @param list<string> $arguments
     * @param list<string> $expectations
     */
    #[DataProvider('imagesCacheProvider')]
    public function testImagesCacheCanBeUsedBeforeItIsCreated(array $arguments, array $expectations): void
    {
        $this->assertCommand(CacheCommand::class, $arguments, 0, $expectations);
    }

    /**
     * @return iterable<string, array{list<string>, list<string>}>
     */
    public static function imagesCacheProvider(): iterable
    {
        yield 'clear' => [['clear', 'images'], ['Images cache cleared.']];
        yield 'clear all' => [['clear', 'all'], ['All caches cleared.']];
        yield 'clear without a type' => [['clear'], ['All caches cleared.']];
        yield 'stats' => [['stats', 'images'], ['Images Cache', 'Items number:']];
        yield 'stats of all the caches' => [['stats', 'all'], ['Config Cache', 'Images Cache', 'Pages Cache']];
    }

    /**
     * @param list<'config'|'images'|'pages'> $cleared
     */
    #[DataProvider('clearedCachesProvider')]
    public function testClearEmptiesOnlyTheGivenCaches(string $type, array $cleared): void
    {
        $caches = $this->fillCaches();

        $this->assertCommand(CacheCommand::class, ['clear', $type], 0);

        $this->assertSame(!in_array('config', $cleared, true), FileSystem::exists($caches['config']), 'config cache');
        $this->assertSame(!in_array('images', $cleared, true), FileSystem::exists($caches['images']), 'images cache');
        $this->assertSame(!in_array('pages', $cleared, true), $this->pagesCache()->has('page'), 'pages cache');
    }

    /**
     * @return iterable<string, array{string, list<'config'|'images'|'pages'>}>
     */
    public static function clearedCachesProvider(): iterable
    {
        yield 'all' => ['all', ['config', 'images', 'pages']];
        yield 'config' => ['config', ['config']];
        yield 'images' => ['images', ['images']];
        yield 'pages' => ['pages', ['pages']];
    }

    /**
     * @param list<string> $arguments
     */
    #[DataProvider('pagesInvalidationProvider')]
    public function testPagesCacheIsInvalidatedByTouchingTheContent(array $arguments): void
    {
        $app = App::instance();
        $app->load();
        $contentPath = (string) $app->site()->contentPath();
        touch($contentPath, time() - 3600);
        clearstatcache();

        $this->assertCommand(CacheCommand::class, $arguments, 0);

        clearstatcache();
        $this->assertGreaterThan(time() - 60, filemtime($contentPath));
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function pagesInvalidationProvider(): iterable
    {
        yield 'invalidate all' => [['invalidate', 'all']];
        yield 'invalidate pages' => [['invalidate', 'pages']];
        yield 'clear pages' => [['clear', 'pages']];
    }

    public function testStatsCountTheItemsOfTheCaches(): void
    {
        $this->fillCaches();
        $this->pagesCache()->set('other', 'content');

        $this->assertCommand(CacheCommand::class, ['stats', 'all'], 0, [
            '/Config Cache\s+Items number: 1\b/',
            '/Images Cache\s+Items number: 1\b/',
            '/Pages Cache\s+Items number: 2\b/',
        ]);
    }

    /**
     * Put an item in the config, images and pages caches, returning the files of the first two
     *
     * @return array{config: string, images: string}
     */
    private function fillCaches(): array
    {
        $app = App::instance();
        $app->load();

        ConfigServiceLoader::clearCache($app->rootPath());
        $config = $app->rootPath() . '/cache/config/config.test.php';
        FileSystem::write($config, '<?php return [];');

        $imagesPath = $app->config()->getString('system.images.processPath');
        if (FileSystem::exists($imagesPath)) {
            FileSystem::delete($imagesPath, recursive: true);
        }
        $images = "{$imagesPath}/image.jpg";
        FileSystem::createDirectory($imagesPath, recursive: true);
        FileSystem::write($images, 'image');

        $this->pagesCache()->clear();
        $this->pagesCache()->set('page', 'content');

        return ['config' => $config, 'images' => $images];
    }

    private function pagesCache(): CacheInterface
    {
        return App::instance()->getService(CacheManager::class)->get('pages');
    }
}
