<?php

namespace Formwork\Tests\Unit\Panel\ContentHistory;

use Formwork\Panel\ContentHistory\ContentHistoryEvent;
use Formwork\Panel\ContentHistory\ContentHistoryItem;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ValueError;

#[CoversClass(ContentHistoryItem::class)]
final class ContentHistoryItemTest extends TestCase
{
    public function testAccessorsExposeConstructorArguments(): void
    {
        $item = new ContentHistoryItem(ContentHistoryEvent::Edited, 'admin', 1700000000);

        $this->assertSame(ContentHistoryEvent::Edited, $item->event());
        $this->assertSame('admin', $item->user());
        $this->assertSame(1700000000, $item->time());
    }

    public function testItemIsSerializedToAnArray(): void
    {
        $item = new ContentHistoryItem(ContentHistoryEvent::Created, 'admin', 42);

        $this->assertSame(['event' => 'created', 'user' => 'admin', 'time' => 42], $item->toArray());
    }

    public function testItemIsCreatedFromAnArray(): void
    {
        $item = ContentHistoryItem::fromArray(['event' => 'edited', 'user' => 'editor', 'time' => 7]);

        $this->assertSame(ContentHistoryEvent::Edited, $item->event());
        $this->assertSame('editor', $item->user());
        $this->assertSame(7, $item->time());
    }

    public function testSerializationRoundTripPreservesTheItem(): void
    {
        $item = new ContentHistoryItem(ContentHistoryEvent::Created, 'admin', 123);

        $this->assertEquals($item, ContentHistoryItem::fromArray($item->toArray()));
    }

    public function testUnknownEventIsRejected(): void
    {
        $this->expectException(ValueError::class);

        ContentHistoryItem::fromArray(['event' => 'deleted', 'user' => 'admin', 'time' => 1]);
    }
}
