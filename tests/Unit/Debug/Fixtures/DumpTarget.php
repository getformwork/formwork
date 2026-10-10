<?php

namespace Formwork\Tests\Unit\Debug\Fixtures;

class DumpTarget
{
    public string $visible = 'public value';

    public ?self $next = null;

    protected int $guarded = 42;

    private bool $hidden = true;

    public function __construct(public readonly string $promoted = 'promoted value') {}
}
