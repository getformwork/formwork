<?php

namespace Formwork\Pages\Traits;

use Formwork\Pages\Page;
use Formwork\Utils\Date;

trait PageStatus
{
    /**
     * Page data
     */
    protected array $data = [];

    /**
     * Page status
     */
    protected string $status;

    /**
     * Get page status
     */
    public function status(): string
    {
        if (isset($this->status)) {
            return $this->status;
        }

        /**
         * @var bool
         */
        $published = $this->get('published', true);

        $now = time();

        /** @var ?string */
        if ($publishDate = $this->data['publishDate'] ?? null) {
            $published = $published && Date::toTimestamp($publishDate) < $now;
        }

        /** @var ?string */
        if ($unpublishDate = $this->data['unpublishDate'] ?? null) {
            $published = $published && Date::toTimestamp($unpublishDate) > $now;
        }

        $this->status = match (true) {
            $published         => Page::PAGE_STATUS_PUBLISHED,
            !$this->routable() => Page::PAGE_STATUS_NOT_ROUTABLE,
            !$published        => Page::PAGE_STATUS_NOT_PUBLISHED
        };

        return $this->status;
    }
}
