<?php

namespace Formwork\Pages\Traits;

use Formwork\Utils\Str;

trait PageUid
{
    protected string $uid;

    public function uid(): string
    {
        if (isset($this->uid)) {
            return $this->uid;
        }
        return $this->uid = Str::chunk(substr(hash('sha256', $this->relativePath), 0, 32), 8, '-');
    }
}
