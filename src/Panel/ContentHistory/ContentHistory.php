<?php

namespace Formwork\Panel\ContentHistory;

use Formwork\Parsers\Json;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;

class ContentHistory
{
    /**
     * History file name
     */
    public const string HISTORY_FILENAME = '.history';

    /**
     * Default history limit
     */
    public const int HISTORY_DEFAULT_LIMIT = 1;

    /**
     * Collection of history items
     */
    protected ContentHistoryItemCollection $items;

    public function __construct(
        protected string $path,
        protected int $limit = self::HISTORY_DEFAULT_LIMIT,
    ) {}

    /**
     * Get history file path
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Check if history file exists
     */
    public function exists(): bool
    {
        return FileSystem::exists(FileSystem::joinPaths($this->path, self::HISTORY_FILENAME));
    }

    /**
     * Get history items
     */
    public function items(): ContentHistoryItemCollection
    {
        if (isset($this->items)) {
            return $this->items;
        }
        if (!$this->exists()) {
            return $this->items = new ContentHistoryItemCollection();
        }
        $items = Json::parse(FileSystem::read(FileSystem::joinPaths($this->path, self::HISTORY_FILENAME)));
        return $this->items = new ContentHistoryItemCollection(Arr::map($items, fn($item) => ContentHistoryItem::fromArray($item)));
    }

    /**
     * Get last history item
     */
    public function lastItem(): ?ContentHistoryItem
    {
        return $this->items()->last();
    }

    /**
     * Check if the content was just created
     */
    public function isJustCreated(): bool
    {
        return $this->lastItem()?->event() === ContentHistoryEvent::Created;
    }

    /**
     * Update history with a new event
     */
    public function update(ContentHistoryEvent $contentHistoryEvent, string $user, int $timestamp): void
    {
        $this->items()->add(new ContentHistoryItem($contentHistoryEvent, $user, $timestamp));
        if ($this->items()->count() > $this->limit) {
            $this->items = $this->items()->slice(-$this->limit);
        }
    }

    /**
     * Save history to file
     */
    public function save(): void
    {
        $data = $this->items()->map(fn($item) => $item->toArray())->toArray();
        FileSystem::write(FileSystem::joinPaths($this->path, self::HISTORY_FILENAME), Json::encode($data));
    }
}
