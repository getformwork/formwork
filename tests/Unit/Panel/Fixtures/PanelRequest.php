<?php

namespace Formwork\Tests\Unit\Panel\Fixtures;

use Formwork\Http\FilesData;
use Formwork\Http\Request;

/**
 * Request whose previous session can be set without having to persist a real one
 */
final class PanelRequest extends Request
{
    public bool $previousSession = false;

    /**
     * Set the uploaded files of the request
     *
     * @param array<string, mixed> $files
     */
    public function withFiles(array $files): void
    {
        $this->files = new FilesData($files);
    }

    public function hasPreviousSession(): bool
    {
        return $this->previousSession || parent::hasPreviousSession();
    }
}
