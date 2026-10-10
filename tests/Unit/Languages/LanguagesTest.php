<?php

namespace Formwork\Tests\Unit\Languages;

use Formwork\Languages\Language;
use Formwork\Languages\LanguageCollection;
use Formwork\Languages\Languages;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Languages::class)]
final class LanguagesTest extends TestCase
{
    public function testLanguagesWithoutOptionsAreEmpty(): void
    {
        $languages = new Languages();

        $this->assertInstanceOf(LanguageCollection::class, $languages->available());
        $this->assertCount(0, $languages->available());
        $this->assertNull($languages->default());
        $this->assertNull($languages->current());
        $this->assertNull($languages->requested());
        $this->assertNull($languages->preferred());
        $this->assertFalse($languages->hasMultiple());
    }

    public function testAvailableLanguagesAreExposed(): void
    {
        $languages = new Languages(['available' => ['en', 'it']]);

        $this->assertSame(['en', 'it'], $languages->available()->keys());
        $this->assertTrue($languages->hasMultiple());
    }

    public function testSingleLanguageIsNotMultiple(): void
    {
        $this->assertFalse((new Languages(['available' => ['en']]))->hasMultiple());
    }

    public function testDefaultLanguageIsResolvedFromTheAvailableOnes(): void
    {
        $languages = new Languages(['available' => ['en', 'it'], 'default' => 'it']);

        $this->assertSame($languages->available()->get('it'), $languages->default());
        $this->assertSame('it', $languages->default()?->code());
    }

    public function testCurrentLanguageDefaultsToTheDefaultOne(): void
    {
        $languages = new Languages(['available' => ['en', 'it'], 'default' => 'en']);

        $this->assertSame($languages->default(), $languages->current());
        $this->assertTrue($languages->isDefault());
    }

    public function testCurrentLanguageCanDifferFromTheDefaultOne(): void
    {
        $languages = new Languages(['available' => ['en', 'it'], 'default' => 'en', 'current' => 'it']);

        $this->assertSame('it', $languages->current()?->code());
        $this->assertSame('en', $languages->default()?->code());
        $this->assertFalse($languages->isDefault());
    }

    public function testRequestedAndPreferredLanguagesAreResolved(): void
    {
        $languages = new Languages(['available' => ['en', 'it', 'de'], 'default' => 'en', 'requested' => 'de', 'preferred' => 'it']);

        $this->assertSame('de', $languages->requested()?->code());
        $this->assertSame('it', $languages->preferred()?->code());
    }

    public function testLanguageObjectsCanBeGivenInsteadOfCodes(): void
    {
        $italian = new Language('it');

        $languages = new Languages(['available' => ['en', 'it'], 'default' => 'en', 'current' => $italian]);

        $this->assertSame($italian, $languages->current());
    }

    public function testUnavailableLanguagesAreNotResolved(): void
    {
        $languages = new Languages(['available' => ['en'], 'default' => 'fr', 'requested' => 'de', 'preferred' => 'it']);

        $this->assertNull($languages->default());
        $this->assertNull($languages->requested());
        $this->assertNull($languages->preferred());
    }

    public function testLanguageCodesAreCaseSensitive(): void
    {
        $languages = new Languages(['available' => ['en', 'it'], 'default' => 'en', 'requested' => 'IT']);

        $this->assertNull($languages->requested());
    }

    public function testWithoutLanguagesTheCurrentLanguageIsNotTheDefaultOne(): void
    {
        $languages = new Languages(['available' => ['en', 'it']]);

        $this->assertNull($languages->default());
        $this->assertNull($languages->current());
    }
}
