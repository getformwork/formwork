<?php

namespace Formwork\Tests\Unit\Events;

use Formwork\Events\Event;
use Formwork\Events\EventDispatcher;
use Formwork\Events\ListenerProvider;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use stdClass;

#[CoversClass(EventDispatcher::class)]
final class EventDispatcherTest extends TestCase
{
    public function testDispatchReturnsTheSameEvent(): void
    {
        $event = new Event('event', []);

        $this->assertSame($event, (new EventDispatcher())->dispatch($event));
    }

    public function testEventsWithoutListenersAreReturnedUntouched(): void
    {
        $event = new Event('event', ['a' => 1]);

        $result = (new EventDispatcher())->dispatch($event);

        $this->assertSame(['a' => 1], $result->data());
        $this->assertFalse($result->isPropagationStopped());
    }

    public function testListenersAreCalledInRegistrationOrder(): void
    {
        $dispatcher = new EventDispatcher();
        $calls = [];

        $dispatcher->on('event', function () use (&$calls): void {
            $calls[] = 'first';
        });
        $dispatcher->on('event', function () use (&$calls): void {
            $calls[] = 'second';
        });
        $dispatcher->on('event', function () use (&$calls): void {
            $calls[] = 'third';
        });

        $dispatcher->dispatch(new Event('event', []));

        $this->assertSame(['first', 'second', 'third'], $calls);
    }

    public function testListenersReceiveTheEvent(): void
    {
        $dispatcher = new EventDispatcher();
        $received = [];
        $event = new Event('event', []);

        $dispatcher->on('event', function (Event $received1) use (&$received): void {
            $received[] = $received1;
        });

        $dispatcher->dispatch($event);

        $this->assertSame([$event], $received);
    }

    public function testListenersCanModifyTheEventForTheFollowingListeners(): void
    {
        $dispatcher = new EventDispatcher();
        $seen = null;

        $dispatcher->on('event', static function (Event $event): void {
            $event->set('value', 'changed');
        });
        $dispatcher->on('event', function (Event $event) use (&$seen): void {
            $seen = $event->get('value');
        });

        $result = $dispatcher->dispatch(new Event('event', ['value' => 'original']));

        $this->assertSame('changed', $seen);
        $this->assertSame('changed', $result->get('value'));
    }

    public function testOnlyListenersOfTheDispatchedEventAreCalled(): void
    {
        $dispatcher = new EventDispatcher();
        $calls = [];

        $dispatcher->on('one', function () use (&$calls): void {
            $calls[] = 'one';
        });
        $dispatcher->on('two', function () use (&$calls): void {
            $calls[] = 'two';
        });

        $dispatcher->dispatch(new Event('two', []));

        $this->assertSame(['two'], $calls);
    }

    public function testStoppingPropagationSkipsTheRemainingListeners(): void
    {
        $dispatcher = new EventDispatcher();
        $calls = [];

        $dispatcher->on('event', function (Event $event) use (&$calls): void {
            $calls[] = 'first';
            $event->stopPropagation();
        });
        $dispatcher->on('event', function () use (&$calls): void {
            $calls[] = 'second';
        });

        $event = $dispatcher->dispatch(new Event('event', []));

        $this->assertSame(['first'], $calls);
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testAlreadyStoppedEventsAreNotDispatched(): void
    {
        $dispatcher = new EventDispatcher();
        $called = false;
        $dispatcher->on('event', function () use (&$called): void {
            $called = true;
        });

        $event = new Event('event', []);
        $event->stopPropagation();
        $dispatcher->dispatch($event);

        $this->assertFalse($called);
    }

    public function testNonStoppableObjectsAreDispatchedByClassName(): void
    {
        $dispatcher = new EventDispatcher();
        $calls = 0;
        $dispatcher->on(stdClass::class, function () use (&$calls): void {
            $calls++;
        });
        $dispatcher->on(stdClass::class, function () use (&$calls): void {
            $calls++;
        });

        $object = new stdClass();

        $this->assertSame($object, $dispatcher->dispatch($object));
        $this->assertSame(2, $calls);
    }

    public function testExceptionsThrownByListenersPropagateAndStopTheDispatch(): void
    {
        $dispatcher = new EventDispatcher();
        $laterCalled = false;
        $dispatcher->on('event', static function (): void {
            throw new RuntimeException('listener failed');
        });
        $dispatcher->on('event', function () use (&$laterCalled): void {
            $laterCalled = true;
        });

        try {
            $dispatcher->dispatch(new Event('event', []));
            $this->fail('The exception thrown by the listener should have propagated.');
        } catch (RuntimeException $exception) {
            $this->assertSame('listener failed', $exception->getMessage());
        }

        $this->assertFalse($laterCalled);
    }

    public function testTheSameEventCanBeDispatchedMoreThanOnce(): void
    {
        $dispatcher = new EventDispatcher();
        $calls = 0;
        $dispatcher->on('event', function () use (&$calls): void {
            $calls++;
        });
        $event = new Event('event', []);

        $dispatcher->dispatch($event);
        $dispatcher->dispatch($event);

        $this->assertSame(2, $calls);
    }

    public function testListenersRegisteredWhileDispatchingAreNotCalledForTheCurrentDispatch(): void
    {
        $dispatcher = new EventDispatcher();
        $calls = [];

        $dispatcher->on('event', function () use ($dispatcher, &$calls): void {
            $calls[] = 'first';
            $dispatcher->on('event', function () use (&$calls): void {
                $calls[] = 'late';
            });
        });

        $dispatcher->dispatch(new Event('event', []));
        $this->assertSame(['first'], $calls);

        $dispatcher->dispatch(new Event('event', []));
        $this->assertSame(['first', 'first', 'late'], $calls);
    }

    public function testCustomListenerProvidersAreUsed(): void
    {
        $provider = new ListenerProvider();
        $called = false;
        $provider->addListener('event', function () use (&$called): void {
            $called = true;
        });

        (new EventDispatcher($provider))->dispatch(new Event('event', []));

        $this->assertTrue($called);
    }

    public function testDispatchersSharingAProviderShareTheirListeners(): void
    {
        $provider = new ListenerProvider();
        $first = new EventDispatcher($provider);
        $second = new EventDispatcher($provider);
        $calls = 0;

        $first->on('event', function () use (&$calls): void {
            $calls++;
        });
        $second->dispatch(new Event('event', []));

        $this->assertSame(1, $calls);
    }

    public function testDispatchersWithDefaultProvidersAreIndependent(): void
    {
        $first = new EventDispatcher();
        $second = new EventDispatcher();
        $calls = 0;

        $first->on('event', function () use (&$calls): void {
            $calls++;
        });
        $second->dispatch(new Event('event', []));

        $this->assertSame(0, $calls);
    }
}
