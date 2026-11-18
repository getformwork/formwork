<?php

namespace Formwork\Tests\Unit\Users;

use Formwork\Tests\TestCase;
use Formwork\Users\ColorScheme;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ColorScheme::class)]
final class ColorSchemeTest extends TestCase
{
    #[DataProvider('schemeProvider')]
    public function testSchemesHaveTheirCompatibleValues(string $value, ColorScheme $colorScheme, string $compatible): void
    {
        $this->assertSame($colorScheme, ColorScheme::from($value));
        $this->assertSame($value, $colorScheme->value);
        $this->assertSame($compatible, $colorScheme->getCompatibleSchemes());
    }

    /**
     * @return iterable<string, array{string, ColorScheme, string}>
     */
    public static function schemeProvider(): iterable
    {
        yield 'light' => ['light', ColorScheme::Light, 'light'];
        yield 'dark' => ['dark', ColorScheme::Dark, 'dark'];
        yield 'auto' => ['auto', ColorScheme::Auto, 'light dark'];
    }
}
