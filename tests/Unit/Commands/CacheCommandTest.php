<?php

namespace Formwork\Tests\Unit\Commands;

use Formwork\Commands\CacheCommand;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

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
}
