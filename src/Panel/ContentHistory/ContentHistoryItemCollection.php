<?php

namespace Formwork\Panel\ContentHistory;

use Formwork\Data\AbstractCollection;

/**
 * @extends AbstractCollection<ContentHistoryItem>
 */
class ContentHistoryItemCollection extends AbstractCollection
{
    protected bool $associative = false;

    protected ?string $dataType = ContentHistoryItem::class;

    protected bool $mutable = true;
}
