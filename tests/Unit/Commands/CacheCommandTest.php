<?php

namespace Formwork\Tests\Unit\Commands;

use Formwork\Commands\CacheCommand;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Commands\Fixtures\RunsCommands;
use Formwork\Tests\UsesApplication;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(CacheCommand::class)]
#[UsesApplication]
final class CacheCommandTest extends TestCase
{
    use RunsCommands;

    public function testHelpListsTheActions(): void
    {
        $result = $this->runCommand(CacheCommand::class);

        $this->assertSame(0, $result['exitCode']);

        foreach (['clear', 'invalidate', 'stats'] as $action) {
            $this->assertStringContainsString($action, $result['output']);
        }
    }

    public function testHelpOptionShowsTheHelp(): void
    {
        $result = $this->runCommand(CacheCommand::class, ['--help']);

        $this->assertSame(0, $result['exitCode']);
        $this->assertStringContainsString('Available Actions:', $result['output']);
    }

    public function testUnknownActionsAreRejected(): void
    {
        $result = $this->runCommand(CacheCommand::class, ['bogus', 'all']);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('Invalid action: bogus.', $result['error'] . $result['output']);
    }

    #[DataProvider('clearTypeProvider')]
    public function testCachesCanBeCleared(string $type, string $message): void
    {
        $result = $this->runCommand(CacheCommand::class, ['clear', $type]);

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);
        $this->assertStringContainsString($message, $result['output']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function clearTypeProvider(): iterable
    {
        yield 'all' => ['all', 'All caches cleared.'];
        yield 'config' => ['config', 'Config cache cleared.'];
        yield 'pages' => ['pages', 'Pages cache cleared.'];
        yield 'images' => ['images', 'Images cache cleared.'];
    }

    public function testClearingAnUnknownCacheTypeFails(): void
    {
        $result = $this->runCommand(CacheCommand::class, ['clear', 'bogus']);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('Invalid cache type for clearing.', $result['error'] . $result['output']);
    }

    public function testClearingWithoutATypeClearsEverything(): void
    {
        $result = $this->runCommand(CacheCommand::class, ['clear']);

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);
        $this->assertStringContainsString('All caches cleared.', $result['output']);
    }

    #[DataProvider('invalidateTypeProvider')]
    public function testCachesCanBeInvalidated(string $type, string $message): void
    {
        $result = $this->runCommand(CacheCommand::class, ['invalidate', $type]);

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);
        $this->assertStringContainsString($message, $result['output']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidateTypeProvider(): iterable
    {
        yield 'all' => ['all', 'All caches invalidated.'];
        yield 'pages' => ['pages', 'Pages cache invalidated.'];
    }

    #[DataProvider('unsupportedInvalidateTypeProvider')]
    public function testOnlyPagesCanBeInvalidated(string $type): void
    {
        $result = $this->runCommand(CacheCommand::class, ['invalidate', $type]);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('Invalid cache type for invalidation.', $result['error'] . $result['output']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsupportedInvalidateTypeProvider(): iterable
    {
        yield 'config' => ['config'];
        yield 'images' => ['images'];
        yield 'unknown' => ['bogus'];
    }

    #[DataProvider('statsTypeProvider')]
    public function testStatsAreShownForEachCache(string $type, string $title): void
    {
        $result = $this->runCommand(CacheCommand::class, ['stats', $type]);

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);
        $this->assertStringContainsString($title, $result['output']);
        $this->assertStringContainsString('Items number:', $result['output']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function statsTypeProvider(): iterable
    {
        yield 'config' => ['config', 'Config Cache'];
        yield 'images' => ['images', 'Images Cache'];
        yield 'pages' => ['pages', 'Pages Cache'];
    }

    public function testStatsOfAllCachesAreShownTogether(): void
    {
        $result = $this->runCommand(CacheCommand::class, ['stats', 'all']);

        $this->assertSame(0, $result['exitCode'], $result['error'] . $result['output']);

        foreach (['Config Cache', 'Images Cache', 'Pages Cache'] as $title) {
            $this->assertStringContainsString($title, $result['output']);
        }
    }

    public function testStatsOfAnUnknownCacheTypeFail(): void
    {
        $result = $this->runCommand(CacheCommand::class, ['stats', 'bogus']);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('Invalid cache type for stats.', $result['error'] . $result['output']);
    }
}
