<?php

namespace Formwork\Pages\Traits;

use Formwork\Data\Attributes\Getter;
use Formwork\Utils\Str;

trait PageUid
{
    /**
     * Page uid (unique identifier)
     */
    protected string $uid;

    /**
     * Get page or site relative path
     */
    abstract public function contentRelativePath(): ?string;

    /**
     * Get the page unique identifier
     */
    #[Getter]
    public function uid(): string
    {
        if (isset($this->uid)) {
            return $this->uid;
        }

        $id = $this->contentRelativePath() ?: spl_object_hash($this);

        return $this->uid = Str::chunk(substr(hash('sha256', (string) $id), 0, 32), 8, '-');
    }
}
