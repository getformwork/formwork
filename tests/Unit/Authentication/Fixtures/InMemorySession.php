<?php

namespace Formwork\Tests\Unit\Authentication\Fixtures;

use Formwork\Http\Session\Session;

/**
 * Session keeping its data in memory, without touching the PHP session machinery
 */
final class InMemorySession extends Session
{
    /**
     * @var array<string, mixed>
     */
    public array $values = [];

    public int $regenerations = 0;

    public function __construct() {}

    public function has(string $key): bool
    {
        return isset($this->values[$key]);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->values[$key]);
    }

    public function regenerate(bool $preserveData = true): void
    {
        $this->regenerations++;
        if (!$preserveData) {
            $this->values = [];
        }
    }
}
