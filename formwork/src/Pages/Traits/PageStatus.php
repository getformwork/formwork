<?php

namespace Formwork\Pages\Traits;

use Formwork\Cms\App;
use Formwork\Model\Attributes\ReadonlyModelProperty;
use Formwork\Pages\Page;
use Formwork\Utils\Date;
use UnexpectedValueException;

trait PageStatus
{
    /**
     * Page status
     */
    #[ReadonlyModelProperty]
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

        $formats = [
            $this->app()->config()->getString('system.date.dateFormat'),
            $this->app()->config()->getString('system.date.datetimeFormat'),
        ];

        if ($publishDate = ($this->data['publishDate'] ?? null)) {
            if (!is_string($publishDate)) {
                throw new UnexpectedValueException('Invalid publish date');
            }

            $published = $published && Date::toTimestamp($publishDate, $formats) < $now;
        }

        if ($unpublishDate = ($this->data['unpublishDate'] ?? null)) {
            if (!is_string($unpublishDate)) {
                throw new UnexpectedValueException('Invalid unpublish date');
            }

            $published = $published && Date::toTimestamp($unpublishDate, $formats) > $now;
        }

        $this->status = match (true) {
            $published  => Page::PAGE_STATUS_PUBLISHED,
            !$published => Page::PAGE_STATUS_NOT_PUBLISHED,
        };

        return $this->status;
    }

    /**
     * Get the application instance
     */
    abstract protected function app(): App;
}
