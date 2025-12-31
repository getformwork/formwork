<?php

namespace Formwork\Events;

use Closure;
use Psr\EventDispatcher\ListenerProviderInterface;

class ListenerProvider implements ListenerProviderInterface
{
    /**
     * @var array<string, list<Closure>>
     */
    private array $listeners = [];

    /**
     * Register a listener for an event class
     *
     * The event name can be either a custom name from an `Event` instance
     * (as returned by `Event::name()`) or the class name of a generic event object.
     *
     * @template TEvent of Event|object
     *
     * @param Closure(TEvent): void $listener
     */
    public function addListener(string $name, Closure $listener): void
    {
        $this->listeners[$name][] = $listener;
    }

    /**
     * Get the listeners for a given event object
     *
     * @return iterable<Closure>
     */
    public function getListenersForEvent(object $event): iterable
    {
        // Get the event name from the Event instance or use the class name
        $name = $event instanceof Event
            ? $event->name()
            : $event::class;

        yield from $this->listeners[$name] ?? [];
    }
}
