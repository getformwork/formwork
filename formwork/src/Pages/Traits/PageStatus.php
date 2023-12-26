<?php

namespace Formwork\Pages\Traits;

use Formwork\Pages\Page;
use Formwork\Utils\Date;

trait PageStatus
{
    /**
     * Page data
     *
     * @var array<string, mixed>
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

        if ($publishDate = ($this->data['publishDate'] ?? null)) {
            /**
             * @var string $publishDate
             */
            $published = $published && Date::toTimestamp($publishDate) < $now;
        }

        if ($unpublishDate = ($this->data['unpublishDate'] ?? null)) {
            /**
             * @var string $unpublishDate
             */
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
