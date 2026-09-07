<?php

namespace Formwork\Panel\ContentHistory;

use Formwork\Data\Contracts\ArraySerializable;

class ContentHistoryItem implements ArraySerializable
{
    final public function __construct(
        protected ContentHistoryEvent $contentHistoryEvent,
        protected string $user,
        protected int $time,
    ) {}

    /**
     * Get the event of the history item
     */
    public function event(): ContentHistoryEvent
    {
        return $this->contentHistoryEvent;
    }

    /**
     * Get the user of the history item
     */
    public function user(): string
    {
        return $this->user;
    }

    /**
     * Get the time of the history item
     */
    public function time(): int
    {
        return $this->time;
    }

    /**
     * @return array{event: string, user: string, time: int}
     */
    public function toArray(): array
    {
        return [
            'event' => $this->contentHistoryEvent->value,
            'user'  => $this->user,
            'time'  => $this->time,
        ];
    }

    /**
     * @param array{event: string, user: string, time: int} $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            ContentHistoryEvent::from($data['event']),
            $data['user'],
            $data['time']
        );
    }
}
