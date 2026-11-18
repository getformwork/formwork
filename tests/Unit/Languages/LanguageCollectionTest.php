<?php

namespace Formwork\Tests\Unit\Languages;

use Formwork\Languages\Language;
use Formwork\Languages\LanguageCollection;
use Formwork\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(LanguageCollection::class)]
final class LanguageCollectionTest extends TestCase
{
    public function testCollectionIsBuiltFromLanguageCodes(): void
    {
        $languageCollection = new LanguageCollection(['en' => 'en', 'it' => 'it']);

        $this->assertCount(2, $languageCollection);
        $this->assertInstanceOf(Language::class, $languageCollection->get('en'));
        $this->assertSame('it', $languageCollection->get('it')?->code());
    }

    public function testLanguagesAreKeyedByCode(): void
    {
        $languageCollection = new LanguageCollection(['en' => 'en', 'it' => 'it', 'de' => 'de']);

        $this->assertSame(['en', 'it', 'de'], $languageCollection->keys());
        $this->assertTrue($languageCollection->has('de'));
        $this->assertFalse($languageCollection->has('fr'));
    }

    public function testEmptyCollectionsHaveNoLanguages(): void
    {
        $languageCollection = new LanguageCollection([]);

        $this->assertCount(0, $languageCollection);
        $this->assertNull($languageCollection->get('en'));
    }

    public function testDuplicatedCodesAreMerged(): void
    {
        $languageCollection = new LanguageCollection(['first' => 'en', 'second' => 'en', 'third' => 'it']);

        $this->assertSame(['en', 'it'], $languageCollection->keys());
    }

    public function testLanguagesCanBeIterated(): void
    {
        $codes = [];
        foreach (new LanguageCollection(['en' => 'en', 'it' => 'it']) as $code => $language) {
            $codes[$code] = (string) $language;
        }

        $this->assertSame(['en' => 'en', 'it' => 'it'], $codes);
    }

    public function testCollectionIsImmutable(): void
    {
        $languageCollection = new LanguageCollection(['en' => 'en']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be set only to associative and mutable collections');
        $languageCollection->set('it', new Language('it'));
    }
}
