<?php

namespace Formwork\Events;

use Closure;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * @since 2.3.0
 */
class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private ListenerProvider $listenerProvider = new ListenerProvider()
    ) {}

    /**
     * Register an event listener
     *
     * @template TEvent of Event|object
     *
     * @param Closure(TEvent): void $listener
     */
    public function on(string $eventName, Closure $listener): void
    {
        $this->listenerProvider->addListener($eventName, $listener);
    }

    /**
     * Dispatch an event
     *
     * @inheritDoc
     */
    public function dispatch(object $event): object
    {
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }

        return $event;
    }
}
