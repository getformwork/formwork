<?php

namespace Formwork\Events;

use Formwork\Data\Traits\DataMultipleGetter;
use Formwork\Data\Traits\DataMultipleSetter;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * @since 2.3.0
 */
class Event implements StoppableEventInterface
{
    use DataMultipleGetter;
    use DataMultipleSetter;

    protected bool $propagationStopped = false;

    /**
     * Create a new Event instance
     *
     * @param string               $name Event name
     * @param array<string, mixed> $data Event data
     */
    public function __construct(protected string $name, array $data)
    {
        $this->data = $data;
    }

    /**
     * Get event name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get event data
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Stop event propagation
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * Check if event propagation is stopped
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
