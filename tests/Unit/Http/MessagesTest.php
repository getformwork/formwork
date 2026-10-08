<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Session\Messages;
use Formwork\Http\Session\MessageType;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Messages::class)]
final class MessagesTest extends TestCase
{
    public function testMessagesAreStoredInTheGivenArray(): void
    {
        $data = [];
        $messages = new Messages($data);

        $messages->add(MessageType::Info, 'first');
        $messages->add(MessageType::Info, 'second');
        $messages->set(MessageType::Error, 'single');

        $this->assertSame(['info' => ['first', 'second'], 'error' => ['single']], $data);
        $this->assertSame($data, $messages->toArray());
    }

    public function testSetReplacesExistingMessagesOfTheSameType(): void
    {
        $data = [];
        $messages = new Messages($data);
        $messages->add(MessageType::Warning, 'old');

        $messages->set(MessageType::Warning, ['new', 'newer']);

        $this->assertSame(['new', 'newer'], $messages->get(MessageType::Warning));
    }

    public function testHasIsFalseForMissingOrEmptyMessages(): void
    {
        $data = [];
        $messages = new Messages($data);

        $this->assertFalse($messages->has(MessageType::Info));

        $messages->set(MessageType::Info, []);
        $this->assertFalse($messages->has(MessageType::Info));

        $messages->add(MessageType::Info, 'hello');
        $this->assertTrue($messages->has(MessageType::Info));
        $this->assertFalse($messages->has(MessageType::Error));
    }

    public function testGetReturnsAndConsumesOnlyTheRequestedType(): void
    {
        $data = [];
        $messages = new Messages($data);
        $messages->add(MessageType::Info, 'hello');
        $messages->add(MessageType::Error, 'bad');

        $this->assertSame(['hello'], $messages->get(MessageType::Info));
        $this->assertSame([], $messages->get(MessageType::Info));
        $this->assertSame([], $messages->get(MessageType::Warning));
        $this->assertSame(['error' => ['bad']], $data);
    }

    public function testGetAllReturnsAndConsumesEveryMessage(): void
    {
        $data = [];
        $messages = new Messages($data);
        $messages->add(MessageType::Info, 'hello');
        $messages->add(MessageType::Error, 'bad');

        $this->assertSame(['info' => ['hello'], 'error' => ['bad']], $messages->getAll());
        $this->assertSame([], $messages->getAll());
        $this->assertSame([], $data);
    }

    public function testRemoveAndRemoveAllDiscardMessages(): void
    {
        $data = [];
        $messages = new Messages($data);
        $messages->add(MessageType::Info, 'hello');
        $messages->add(MessageType::Error, 'bad');

        $messages->remove(MessageType::Info);
        $this->assertSame(['error' => ['bad']], $data);

        $messages->removeAll();
        $this->assertSame([], $data);
    }
}
