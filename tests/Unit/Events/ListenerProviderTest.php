<?php

namespace Formwork\Tests\Unit\Events;

use ArrayObject;
use Formwork\Events\Event;
use Formwork\Events\ListenerProvider;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

#[CoversClass(ListenerProvider::class)]
final class ListenerProviderTest extends TestCase
{
    public function testEventsWithoutListenersHaveNoListeners(): void
    {
        $listenerProvider = new ListenerProvider();

        $this->assertSame([], $this->listeners($listenerProvider, new Event('unknown', [])));
    }

    public function testListenersAreReturnedInRegistrationOrder(): void
    {
        $listenerProvider = new ListenerProvider();
        $first = static function (): void {};
        $second = static function (): void {};
        $third = static function (): void {};

        $listenerProvider->addListener('event', $first);
        $listenerProvider->addListener('event', $second);
        $listenerProvider->addListener('event', $third);

        $this->assertSame([$first, $second, $third], $this->listeners($listenerProvider, new Event('event', [])));
    }

    public function testListenersAreSeparatedByEventName(): void
    {
        $listenerProvider = new ListenerProvider();
        $one = static function (): void {};
        $two = static function (): void {};

        $listenerProvider->addListener('one', $one);
        $listenerProvider->addListener('two', $two);

        $this->assertSame([$one], $this->listeners($listenerProvider, new Event('one', [])));
        $this->assertSame([$two], $this->listeners($listenerProvider, new Event('two', [])));
    }

    public function testNamedEventsAreMatchedByNameAndNotByClass(): void
    {
        $listenerProvider = new ListenerProvider();
        $byClass = static function (): void {};

        $listenerProvider->addListener(Event::class, $byClass);

        $this->assertSame([], $this->listeners($listenerProvider, new Event('custom', [])));
    }

    public function testGenericObjectsAreMatchedByClassName(): void
    {
        $listenerProvider = new ListenerProvider();
        $listener = static function (): void {};

        $listenerProvider->addListener(stdClass::class, $listener);

        $this->assertSame([$listener], $this->listeners($listenerProvider, new stdClass()));
        $this->assertSame([], $this->listeners($listenerProvider, new ArrayObject()));
    }

    public function testTheSameListenerCanBeRegisteredTwice(): void
    {
        $listenerProvider = new ListenerProvider();
        $listener = static function (): void {};

        $listenerProvider->addListener('event', $listener);
        $listenerProvider->addListener('event', $listener);

        $this->assertCount(2, $this->listeners($listenerProvider, new Event('event', [])));
    }

    public function testEventNamesAreCaseSensitive(): void
    {
        $listenerProvider = new ListenerProvider();
        $listenerProvider->addListener('Event', static function (): void {});

        $this->assertSame([], $this->listeners($listenerProvider, new Event('event', [])));
    }

    public function testListenersAddedAfterwardsAreIncluded(): void
    {
        $listenerProvider = new ListenerProvider();
        $event = new Event('event', []);
        $late = static function (): void {};

        $this->assertSame([], $this->listeners($listenerProvider, $event));

        $listenerProvider->addListener('event', $late);

        $this->assertSame([$late], $this->listeners($listenerProvider, $event));
    }

    /**
     * @return list<callable>
     */
    private function listeners(ListenerProvider $listenerProvider, object $event): array
    {
        return iterator_to_array($listenerProvider->getListenersForEvent($event), false);
    }
}
