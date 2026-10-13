<?php

namespace Formwork\Tests\Unit\Languages;

use Formwork\Languages\Language;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Stringable;

#[CoversClass(Language::class)]
final class LanguageTest extends TestCase
{
    public function testKnownLanguagesHaveNames(): void
    {
        $language = new Language('it');

        $this->assertSame('it', $language->code());
        $this->assertSame('Italian', $language->name());
        $this->assertSame('Italiano', $language->nativeName());
    }

    public function testUnknownLanguagesKeepTheirCodeWithoutNames(): void
    {
        $language = new Language('xx');

        $this->assertSame('xx', $language->code());
        $this->assertNull($language->name());
        $this->assertNull($language->nativeName());
    }

    public function testLanguagesAreConvertedToTheirCode(): void
    {
        $language = new Language('de');

        $this->assertInstanceOf(Stringable::class, $language);
        $this->assertSame('de', (string) $language);
        $this->assertSame('/de/page', "/{$language}/page");
    }

    public function testRegionalCodesAreNotKnownLanguages(): void
    {
        $language = new Language('en-GB');

        $this->assertSame('en-GB', $language->code());
        $this->assertNull($language->name());
    }

    public function testCodesAreNotNormalized(): void
    {
        $language = new Language('IT');

        $this->assertSame('IT', $language->code());
        $this->assertNull($language->name());
    }

    public function testEmptyCodesAreAllowed(): void
    {
        $language = new Language('');

        $this->assertSame('', (string) $language);
        $this->assertNull($language->name());
    }
}
