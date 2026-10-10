<?php

namespace Formwork\Tests\Unit\Languages;

use Formwork\Languages\Language;
use Formwork\Languages\LanguageCollection;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(LanguageCollection::class)]
final class LanguageCollectionTest extends TestCase
{
    public function testCollectionIsBuiltFromLanguageCodes(): void
    {
        $collection = new LanguageCollection(['en', 'it']);

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(Language::class, $collection->get('en'));
        $this->assertSame('it', $collection->get('it')->code());
    }

    public function testLanguagesAreKeyedByCode(): void
    {
        $collection = new LanguageCollection(['en', 'it', 'de']);

        $this->assertSame(['en', 'it', 'de'], $collection->keys());
        $this->assertTrue($collection->has('de'));
        $this->assertFalse($collection->has('fr'));
    }

    public function testEmptyCollection(): void
    {
        $collection = new LanguageCollection([]);

        $this->assertCount(0, $collection);
        $this->assertNull($collection->get('en'));
    }

    public function testDuplicatedCodesAreMerged(): void
    {
        $collection = new LanguageCollection(['en', 'en', 'it']);

        $this->assertSame(['en', 'it'], $collection->keys());
    }

    public function testLanguagesCanBeIterated(): void
    {
        $codes = [];
        foreach (new LanguageCollection(['en', 'it']) as $code => $language) {
            $codes[$code] = (string) $language;
        }

        $this->assertSame(['en' => 'en', 'it' => 'it'], $codes);
    }

    public function testOnlyLanguagesCanBeAdded(): void
    {
        $collection = new LanguageCollection(['en']);

        $this->expectException(\Throwable::class);
        $collection->set('it', 'not a language');
    }
}
