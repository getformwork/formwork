<?php

namespace Formwork\Tests\Unit\Log\Fixtures;

use DateTimeInterface;
use Formwork\Log\Handler\AbstractHandler;

final class ExposedHandler extends AbstractHandler
{
    /**
     * @var list<string>
     */
    public array $handled = [];

    public function handle(DateTimeInterface $datetime, string $level, string $message, array $context): void
    {
        if ($this->shouldHandle($level)) {
            $this->handled[] = $level;
        }
    }

    public function accepts(string $level): bool
    {
        return $this->shouldHandle($level);
    }
}
