<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Session\Messages;
use Formwork\Http\Session\MessageType;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Messages::class)]
final class MessagesTest extends TestCase
{
    public function testMessagesCanBeAddedReadAndRemoved(): void
    {
        $data = [];
        $messages = new Messages($data);
        $messages->add(MessageType::Info, 'hello');
        $messages->set(MessageType::Error, ['bad']);

        $this->assertTrue($messages->has(MessageType::Info));
        $this->assertSame(['hello'], $messages->get(MessageType::Info));
        $this->assertSame(['error' => ['bad']], $messages->getAll());
        $this->assertSame([], $data);
    }
}
