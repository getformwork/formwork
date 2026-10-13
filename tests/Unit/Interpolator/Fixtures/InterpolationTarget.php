<?php

namespace Formwork\Tests\Unit\Interpolator\Fixtures;

class InterpolationTarget
{
    public const string PUBLIC_CONSTANT = 'constant';

    private const string PRIVATE_CONSTANT = 'private constant';

    public string $publicProperty = 'public';

    public ?string $nullProperty = null;

    protected string $protectedProperty = 'protected';

    private string $privateProperty = 'private';

    public static function staticMethod(): string
    {
        return 'static';
    }

    public function method(mixed $argument = null): string
    {
        return 'method(' . json_encode($argument) . ')';
    }

    public function variadic(mixed ...$arguments): int
    {
        return count($arguments);
    }

    /**
     * @return array<array-key, string>
     */
    public function items(): array
    {
        return ['key' => 'value', 1 => 'one'];
    }

    public function self(): self
    {
        return $this;
    }

    public function nothing(): ?self
    {
        return null;
    }

    protected function protectedMethod(): string
    {
        return $this->protectedProperty;
    }

    private function privateMethod(): string
    {
        return $this->privateProperty;
    }
}
