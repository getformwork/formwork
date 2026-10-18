<?php

namespace Formwork\Tests\Unit\Schemes;

use Formwork\Schemes\SchemeOptions;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SchemeOptions::class)]
final class SchemeOptionsTest extends TestCase
{
    public function testOptionsAreReadableByKey(): void
    {
        $schemeOptions = new SchemeOptions(['num' => 3, 'flag' => true, 'nested' => ['key' => 'value']]);

        $this->assertSame(3, $schemeOptions->get('num'));
        $this->assertTrue($schemeOptions->get('flag'));
        $this->assertSame('value', $schemeOptions->get('nested.key'));
        $this->assertTrue($schemeOptions->has('num'));
    }

    public function testMissingOptionsFallBackToTheDefault(): void
    {
        $schemeOptions = new SchemeOptions([]);

        $this->assertFalse($schemeOptions->has('missing'));
        $this->assertNull($schemeOptions->get('missing'));
        $this->assertSame('fallback', $schemeOptions->get('missing', 'fallback'));
    }

    public function testToArrayReturnsTheOptions(): void
    {
        $this->assertSame(['a' => 1], (new SchemeOptions(['a' => 1]))->toArray());
    }
}
