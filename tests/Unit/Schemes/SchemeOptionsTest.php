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
        $options = new SchemeOptions(['num' => 3, 'flag' => true, 'nested' => ['key' => 'value']]);

        $this->assertSame(3, $options->get('num'));
        $this->assertTrue($options->get('flag'));
        $this->assertSame('value', $options->get('nested.key'));
        $this->assertTrue($options->has('num'));
    }

    public function testMissingOptionsFallBackToTheDefault(): void
    {
        $options = new SchemeOptions([]);

        $this->assertFalse($options->has('missing'));
        $this->assertNull($options->get('missing'));
        $this->assertSame('fallback', $options->get('missing', 'fallback'));
    }

    public function testToArrayReturnsTheOptions(): void
    {
        $this->assertSame(['a' => 1], (new SchemeOptions(['a' => 1]))->toArray());
    }
}
