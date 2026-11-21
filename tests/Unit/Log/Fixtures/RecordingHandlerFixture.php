<?php

namespace Formwork\Tests\Unit\Log\Fixtures;

use DateTimeInterface;
use Formwork\Log\Logger;
use Formwork\Log\Handler\ChannelAwareHandlerInterface;

final class RecordingHandlerFixture implements ChannelAwareHandlerInterface
{
    /**
     * @var list<array{DateTimeInterface, string, string, array<string, mixed>, string}>
     */
    public array $records = [];

    public function handle(DateTimeInterface $datetime, string $level, string $message, array $context, string $channel = Logger::DEFAULT_CHANNEL): void
    {
        $this->records[] = [$datetime, $level, $message, $context, $channel];
    }
}
