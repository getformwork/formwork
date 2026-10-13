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
        $provider = new ListenerProvider();

        $this->assertSame([], $this->listeners($provider, new Event('unknown', [])));
    }

    public function testListenersAreReturnedInRegistrationOrder(): void
    {
        $provider = new ListenerProvider();
        $first = static function (): void {};
        $second = static function (): void {};
        $third = static function (): void {};

        $provider->addListener('event', $first);
        $provider->addListener('event', $second);
        $provider->addListener('event', $third);

        $this->assertSame([$first, $second, $third], $this->listeners($provider, new Event('event', [])));
    }

    public function testListenersAreSeparatedByEventName(): void
    {
        $provider = new ListenerProvider();
        $one = static function (): void {};
        $two = static function (): void {};

        $provider->addListener('one', $one);
        $provider->addListener('two', $two);

        $this->assertSame([$one], $this->listeners($provider, new Event('one', [])));
        $this->assertSame([$two], $this->listeners($provider, new Event('two', [])));
    }

    public function testNamedEventsAreMatchedByNameAndNotByClass(): void
    {
        $provider = new ListenerProvider();
        $byClass = static function (): void {};

        $provider->addListener(Event::class, $byClass);

        $this->assertSame([], $this->listeners($provider, new Event('custom', [])));
    }

    public function testGenericObjectsAreMatchedByClassName(): void
    {
        $provider = new ListenerProvider();
        $listener = static function (): void {};

        $provider->addListener(stdClass::class, $listener);

        $this->assertSame([$listener], $this->listeners($provider, new stdClass()));
        $this->assertSame([], $this->listeners($provider, new ArrayObject()));
    }

    public function testTheSameListenerCanBeRegisteredTwice(): void
    {
        $provider = new ListenerProvider();
        $listener = static function (): void {};

        $provider->addListener('event', $listener);
        $provider->addListener('event', $listener);

        $this->assertCount(2, $this->listeners($provider, new Event('event', [])));
    }

    public function testEventNamesAreCaseSensitive(): void
    {
        $provider = new ListenerProvider();
        $provider->addListener('Event', static function (): void {});

        $this->assertSame([], $this->listeners($provider, new Event('event', [])));
    }

    public function testListenersAddedAfterwardsAreIncluded(): void
    {
        $provider = new ListenerProvider();
        $event = new Event('event', []);
        $late = static function (): void {};

        $this->assertSame([], $this->listeners($provider, $event));

        $provider->addListener('event', $late);

        $this->assertSame([$late], $this->listeners($provider, $event));
    }

    /**
     * @return list<callable>
     */
    private function listeners(ListenerProvider $provider, object $event): array
    {
        return iterator_to_array($provider->getListenersForEvent($event), false);
    }
}
