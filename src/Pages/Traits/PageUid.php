<?php

namespace Formwork\Pages\Traits;

use Formwork\Model\Attributes\ReadonlyModelProperty;
use Formwork\Utils\Str;

trait PageUid
{
    /**
     * Page uid (unique identifier)
     */
    #[ReadonlyModelProperty]
    protected string $uid;

    /**
     * Get page or site relative path
     */
    abstract public function contentRelativePath(): ?string;

    /**
     * Get the page unique identifier
     */
    public function uid(): string
    {
        if (isset($this->uid)) {
            return $this->uid;
        }

        $id = $this->contentRelativePath() ?: spl_object_hash($this);

        return $this->uid = Str::chunk(substr(hash('sha256', (string) $id), 0, 32), 8, '-');
    }
}
