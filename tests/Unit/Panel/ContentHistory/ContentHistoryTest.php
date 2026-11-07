<?php

namespace Formwork\Tests\Unit\Panel\ContentHistory;

use Formwork\Panel\ContentHistory\ContentHistory;
use Formwork\Panel\ContentHistory\ContentHistoryEvent;
use Formwork\Panel\ContentHistory\ContentHistoryItem;
use Formwork\Panel\ContentHistory\ContentHistoryItemCollection;
use Formwork\Parsers\Json;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ContentHistory::class)]
#[CoversClass(ContentHistoryItemCollection::class)]
#[UsesTempDirectory]
final class ContentHistoryTest extends TestCase
{
    private string $historyFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->historyFile = TESTS_TMP_PATH . '/' . ContentHistory::HISTORY_FILENAME;
    }

    public function testPathIsExposed(): void
    {
        $this->assertSame(TESTS_TMP_PATH, (new ContentHistory(TESTS_TMP_PATH))->path());
    }

    public function testHistoryDoesNotExistUntilItIsSaved(): void
    {
        $history = new ContentHistory(TESTS_TMP_PATH);

        $this->assertFalse($history->exists());
        $this->assertCount(0, $history->items());
        $this->assertNull($history->lastItem());
        $this->assertFalse($history->isJustCreated());
    }

    public function testItemsAreLoadedFromTheHistoryFile(): void
    {
        FileSystem::write($this->historyFile, Json::encode([
            ['event' => 'created', 'user' => 'admin', 'time' => 1],
            ['event' => 'edited', 'user' => 'editor', 'time' => 2],
        ]));

        $history = new ContentHistory(TESTS_TMP_PATH, 5);

        $this->assertTrue($history->exists());
        $this->assertCount(2, $history->items());
        $this->assertSame('editor', $history->lastItem()?->user());
        $this->assertFalse($history->isJustCreated());
    }

    public function testEmptyHistoryFileResultsInNoItems(): void
    {
        FileSystem::write($this->historyFile, Json::encode([]));

        $history = new ContentHistory(TESTS_TMP_PATH);

        $this->assertTrue($history->exists());
        $this->assertCount(0, $history->items());
    }

    public function testItemsAreLoadedOnlyOnce(): void
    {
        $history = new ContentHistory(TESTS_TMP_PATH);

        $this->assertSame($history->items(), $history->items());
    }

    public function testJustCreatedContentIsDetected(): void
    {
        $history = new ContentHistory(TESTS_TMP_PATH);

        $history->update(ContentHistoryEvent::Created, 'admin', 1);

        $this->assertTrue($history->isJustCreated());

        $history->update(ContentHistoryEvent::Edited, 'admin', 2);

        $this->assertFalse($history->isJustCreated());
    }

    public function testUpdateAppendsItems(): void
    {
        $history = new ContentHistory(TESTS_TMP_PATH, 3);

        $history->update(ContentHistoryEvent::Created, 'first', 1);
        $history->update(ContentHistoryEvent::Edited, 'second', 2);

        $this->assertCount(2, $history->items());
        $this->assertInstanceOf(ContentHistoryItem::class, $history->lastItem());
        $this->assertSame('second', $history->lastItem()->user());
        $this->assertSame(2, $history->lastItem()->time());
    }

    public function testDefaultLimitKeepsOnlyTheLastItem(): void
    {
        $history = new ContentHistory(TESTS_TMP_PATH);

        $history->update(ContentHistoryEvent::Created, 'first', 1);
        $history->update(ContentHistoryEvent::Edited, 'second', 2);

        $this->assertSame(ContentHistory::HISTORY_DEFAULT_LIMIT, 1);
        $this->assertCount(1, $history->items());
        $this->assertSame('second', $history->lastItem()?->user());
    }

    public function testOldestItemsAreDroppedWhenTheLimitIsExceeded(): void
    {
        $history = new ContentHistory(TESTS_TMP_PATH, 2);

        foreach (['a', 'b', 'c', 'd'] as $time => $user) {
            $history->update(ContentHistoryEvent::Edited, $user, $time);
        }

        $this->assertSame(
            ['c', 'd'],
            $history->items()->map(fn(ContentHistoryItem $item) => $item->user())->toArray(),
        );
    }

    #[DataProvider('historyLimitProvider')]
    public function testItemCountNeverExceedsTheLimit(int $limit, int $updates, int $expected): void
    {
        $history = new ContentHistory(TESTS_TMP_PATH, $limit);

        for ($i = 1; $i <= $updates; ++$i) {
            $history->update(ContentHistoryEvent::Edited, 'user' . $i, $i);
        }

        $this->assertCount($expected, $history->items());
        $this->assertSame('user' . $updates, $history->lastItem()?->user());
    }

    /**
     * @return iterable<string, array{int, int, int}>
     */
    public static function historyLimitProvider(): iterable
    {
        yield 'below the limit' => [5, 3, 3];
        yield 'at the limit' => [3, 3, 3];
        yield 'above the limit' => [3, 10, 3];
        yield 'limit of one' => [1, 4, 1];
    }

    public function testNonPositiveLimitNeverKeepsMoreThanTheLimit(): void
    {
        $history = new ContentHistory(TESTS_TMP_PATH, 0);

        $history->update(ContentHistoryEvent::Created, 'first', 1);
        $history->update(ContentHistoryEvent::Edited, 'second', 2);

        $this->assertCount(0, $history->items());
    }

    public function testSavedHistoryCanBeReloaded(): void
    {
        $history = new ContentHistory(TESTS_TMP_PATH, 3);
        $history->update(ContentHistoryEvent::Created, 'admin', 10);
        $history->update(ContentHistoryEvent::Edited, 'editor', 20);
        $history->save();

        $this->assertFileExists($this->historyFile);

        $reloaded = new ContentHistory(TESTS_TMP_PATH, 3);

        $this->assertTrue($reloaded->exists());
        $this->assertSame(
            [['event' => 'created', 'user' => 'admin', 'time' => 10], ['event' => 'edited', 'user' => 'editor', 'time' => 20]],
            $reloaded->items()->map(fn(ContentHistoryItem $item) => $item->toArray())->toArray(),
        );
    }

    public function testEmptyHistoryIsSavedAsEmptyList(): void
    {
        (new ContentHistory(TESTS_TMP_PATH))->save();

        $this->assertSame([], Json::parse(FileSystem::read($this->historyFile)));
    }
}
