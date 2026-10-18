<?php

namespace Formwork\Tests\Unit\Events;

use ArrayObject;
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
        $eventDispatcher = new EventDispatcher();
        $calls = [];

        $eventDispatcher->on('event', function () use (&$calls): void {
            $calls[] = 'first';
        });
        $eventDispatcher->on('event', function () use (&$calls): void {
            $calls[] = 'second';
        });
        $eventDispatcher->on('event', function () use (&$calls): void {
            $calls[] = 'third';
        });

        $eventDispatcher->dispatch(new Event('event', []));

        $this->assertSame(['first', 'second', 'third'], $calls);
    }

    public function testListenersReceiveTheEvent(): void
    {
        $eventDispatcher = new EventDispatcher();
        $received = [];
        $event = new Event('event', []);

        $eventDispatcher->on('event', function (Event $event) use (&$received): void {
            $received[] = $event;
        });

        $eventDispatcher->dispatch($event);

        $this->assertSame([$event], $received);
    }

    public function testListenersCanModifyTheEventForTheFollowingListeners(): void
    {
        $eventDispatcher = new EventDispatcher();
        $seen = null;

        $eventDispatcher->on('event', static function (Event $event): void {
            $event->set('value', 'changed');
        });
        $eventDispatcher->on('event', function (Event $event) use (&$seen): void {
            $seen = $event->get('value');
        });

        $result = $eventDispatcher->dispatch(new Event('event', ['value' => 'original']));

        $this->assertSame('changed', $seen);
        $this->assertSame('changed', $result->get('value'));
    }

    public function testOnlyListenersOfTheDispatchedEventAreCalled(): void
    {
        $eventDispatcher = new EventDispatcher();
        $calls = [];

        $eventDispatcher->on('one', function () use (&$calls): void {
            $calls[] = 'one';
        });
        $eventDispatcher->on('two', function () use (&$calls): void {
            $calls[] = 'two';
        });

        $eventDispatcher->dispatch(new Event('two', []));

        $this->assertSame(['two'], $calls);
    }

    public function testStoppingPropagationSkipsTheRemainingListeners(): void
    {
        $eventDispatcher = new EventDispatcher();
        $calls = [];

        $eventDispatcher->on('event', function (Event $event) use (&$calls): void {
            $calls[] = 'first';
            $event->stopPropagation();
        });
        $eventDispatcher->on('event', function () use (&$calls): void {
            $calls[] = 'second';
        });

        $event = $eventDispatcher->dispatch(new Event('event', []));

        $this->assertSame(['first'], $calls);
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testAlreadyStoppedEventsAreNotDispatched(): void
    {
        $eventDispatcher = new EventDispatcher();
        $called = false;
        $eventDispatcher->on('event', function () use (&$called): void {
            $called = true;
        });

        $event = new Event('event', []);
        $event->stopPropagation();
        $eventDispatcher->dispatch($event);

        $this->assertFalse($called);
    }

    public function testNonStoppableObjectsAreDispatchedByClassName(): void
    {
        $eventDispatcher = new EventDispatcher();
        $calls = 0;
        $eventDispatcher->on(stdClass::class, function () use (&$calls): void {
            $calls++;
        });
        $eventDispatcher->on(stdClass::class, function () use (&$calls): void {
            $calls++;
        });

        $object = new stdClass();

        $this->assertSame($object, $eventDispatcher->dispatch($object));
        $this->assertSame(2, $calls);
    }

    public function testExceptionsThrownByListenersPropagateAndStopTheDispatch(): void
    {
        $eventDispatcher = new EventDispatcher();
        $laterCalled = false;
        $eventDispatcher->on('event', static function (): void {
            throw new RuntimeException('listener failed');
        });
        $eventDispatcher->on('event', function () use (&$laterCalled): void {
            $laterCalled = true;
        });

        $runtimeException = $this->captureException(RuntimeException::class, static fn() => $eventDispatcher->dispatch(new Event('event', [])));
        $this->assertSame('listener failed', $runtimeException->getMessage());

        $this->assertFalse($laterCalled);
    }

    public function testTheSameEventCanBeDispatchedMoreThanOnce(): void
    {
        $eventDispatcher = new EventDispatcher();
        $calls = 0;
        $eventDispatcher->on('event', function () use (&$calls): void {
            $calls++;
        });
        $event = new Event('event', []);

        $eventDispatcher->dispatch($event);
        $eventDispatcher->dispatch($event);

        $this->assertSame(2, $calls);
    }

    public function testListenersRegisteredWhileDispatchingAreNotCalledForTheCurrentDispatch(): void
    {
        $eventDispatcher = new EventDispatcher();
        $arrayObject = new ArrayObject();

        $eventDispatcher->on('event', function () use ($eventDispatcher, $arrayObject): void {
            $arrayObject[] = 'first';
            $eventDispatcher->on('event', function () use ($arrayObject): void {
                $arrayObject[] = 'late';
            });
        });

        $eventDispatcher->dispatch(new Event('event', []));
        $this->assertSame(['first'], $arrayObject->getArrayCopy());

        $eventDispatcher->dispatch(new Event('event', []));
        $this->assertSame(['first', 'first', 'late'], $arrayObject->getArrayCopy());
    }

    public function testCustomListenerProvidersAreUsed(): void
    {
        $listenerProvider = new ListenerProvider();
        $called = false;
        $listenerProvider->addListener('event', function () use (&$called): void {
            $called = true;
        });

        (new EventDispatcher($listenerProvider))->dispatch(new Event('event', []));

        $this->assertTrue($called);
    }

    public function testDispatchersSharingAProviderShareTheirListeners(): void
    {
        $listenerProvider = new ListenerProvider();
        $first = new EventDispatcher($listenerProvider);
        $second = new EventDispatcher($listenerProvider);
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
