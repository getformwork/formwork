<?php

namespace Formwork\Tests\Unit\Log\Fixtures;

use DateTimeInterface;
use Formwork\Log\Handler\HandlerInterface;

final class RecordingHandler implements HandlerInterface
{
    /**
     * @var list<array{DateTimeInterface, string, string, array<mixed>}>
     */
    public array $records = [];

    public function handle(DateTimeInterface $datetime, string $level, string $message, array $context): void
    {
        $this->records[] = [$datetime, $level, $message, $context];
    }
}
