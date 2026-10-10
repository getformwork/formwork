<?php

namespace Formwork\Tests\Unit\Events;

use Formwork\Events\Event;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\EventDispatcher\StoppableEventInterface;

#[CoversClass(Event::class)]
final class EventTest extends TestCase
{
    public function testNameAndDataAreExposed(): void
    {
        $event = new Event('page.saved', ['id' => 5, 'title' => 'Title']);

        $this->assertSame('page.saved', $event->name());
        $this->assertSame(['id' => 5, 'title' => 'Title'], $event->data());
    }

    public function testEventsAreStoppable(): void
    {
        $this->assertInstanceOf(StoppableEventInterface::class, new Event('name', []));
    }

    public function testPropagationIsNotStoppedByDefault(): void
    {
        $this->assertFalse((new Event('name', []))->isPropagationStopped());
    }

    public function testPropagationCanBeStopped(): void
    {
        $event = new Event('name', []);

        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }

    public function testStoppingPropagationTwiceIsHarmless(): void
    {
        $event = new Event('name', []);

        $event->stopPropagation();
        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }

    public function testDataCanBeReadWithDefaults(): void
    {
        $event = new Event('name', ['present' => 'value', 'nested' => ['key' => 'inner']]);

        $this->assertTrue($event->has('present'));
        $this->assertFalse($event->has('missing'));
        $this->assertSame('value', $event->get('present'));
        $this->assertNull($event->get('missing'));
        $this->assertSame('fallback', $event->get('missing', 'fallback'));
        $this->assertSame('inner', $event->get('nested.key'));
        $this->assertTrue($event->has('nested.key'));
    }

    public function testMultipleValuesCanBeRead(): void
    {
        $event = new Event('name', ['a' => 1, 'b' => 2]);

        $this->assertTrue($event->hasMultiple(['a', 'b']));
        $this->assertFalse($event->hasMultiple(['a', 'c']));
        $this->assertSame(['a' => 1, 'c' => 'default'], $event->getMultiple(['a', 'c'], 'default'));
    }

    public function testDataCanBeModifiedByListeners(): void
    {
        $event = new Event('name', ['a' => 1]);

        $event->set('a', 2);
        $event->set('b.c', 3);
        $event->setMultiple(['d' => 4, 'e' => 5]);

        $this->assertSame(['a' => 2, 'b' => ['c' => 3], 'd' => 4, 'e' => 5], $event->data());
    }

    public function testDataCanBeRemoved(): void
    {
        $event = new Event('name', ['a' => 1, 'b' => 2, 'c' => 3]);

        $event->remove('a');
        $event->removeMultiple(['b']);

        $this->assertSame(['c' => 3], $event->data());
    }

    public function testNameCannotBeChangedThroughData(): void
    {
        $event = new Event('name', []);

        $event->set('name', 'other');

        $this->assertSame('name', $event->name());
    }

    public function testEmptyEventsHaveNoData(): void
    {
        $event = new Event('name', []);

        $this->assertSame([], $event->data());
        $this->assertFalse($event->has('anything'));
    }
}
