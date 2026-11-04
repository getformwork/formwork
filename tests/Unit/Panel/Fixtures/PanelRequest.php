<?php

namespace Formwork\Tests\Unit\Panel\Fixtures;

use Formwork\Http\Request;

/**
 * Request whose previous session can be set without having to persist a real one
 */
final class PanelRequest extends Request
{
    public bool $previousSession = false;

    public function hasPreviousSession(): bool
    {
        return $this->previousSession || parent::hasPreviousSession();
    }
}
