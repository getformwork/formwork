<?php

namespace Formwork\Tests\Unit\Users;

use Formwork\Tests\TestCase;
use Formwork\Users\ColorScheme;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ValueError;

#[CoversClass(ColorScheme::class)]
final class ColorSchemeTest extends TestCase
{
    #[DataProvider('schemeProvider')]
    public function testSchemes(string $value, ColorScheme $scheme, string $compatible): void
    {
        $this->assertSame($scheme, ColorScheme::from($value));
        $this->assertSame($value, $scheme->value);
        $this->assertSame($compatible, $scheme->getCompatibleSchemes());
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

    public function testOnlyThreeSchemesExist(): void
    {
        $this->assertCount(3, ColorScheme::cases());
    }

    public function testUnknownSchemesAreRejected(): void
    {
        $this->assertNull(ColorScheme::tryFrom('sepia'));
        $this->assertNull(ColorScheme::tryFrom('Light'));

        $this->expectException(ValueError::class);
        ColorScheme::from('sepia');
    }
}
