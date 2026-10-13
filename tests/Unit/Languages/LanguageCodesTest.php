<?php

namespace Formwork\Tests\Unit\Languages;

use Formwork\Languages\LanguageCodes;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(LanguageCodes::class)]
final class LanguageCodesTest extends TestCase
{
    public function testClassCannotBeInstantiated(): void
    {
        $this->expectException(LogicException::class);
        new LanguageCodes();
    }

    #[DataProvider('knownCodeProvider')]
    public function testKnownCodes(string $code, string $name, string $nativeName): void
    {
        $this->assertTrue(LanguageCodes::hasCode($code));
        $this->assertSame($name, LanguageCodes::codeToName($code));
        $this->assertSame($nativeName, LanguageCodes::codeToNativeName($code));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function knownCodeProvider(): iterable
    {
        yield 'English' => ['en', 'English', 'English'];
        yield 'Italian' => ['it', 'Italian', 'Italiano'];
        yield 'German' => ['de', 'German', 'Deutsch'];
        yield 'Arabic' => ['ar', 'Arabic', 'العربية'];
        yield 'Chinese' => ['zh', 'Chinese', '中文'];
        yield 'Zulu' => ['zu', 'Zulu', 'isiZulu'];
    }

    #[DataProvider('unknownCodeProvider')]
    public function testUnknownCodes(string $code): void
    {
        $this->assertFalse(LanguageCodes::hasCode($code));

        try {
            LanguageCodes::codeToName($code);
            $this->fail('codeToName() should reject the code.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString(sprintf('Invalid language code "%s"', $code), $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        LanguageCodes::codeToNativeName($code);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unknownCodeProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'unknown' => ['xx'];
        yield 'uppercase' => ['EN'];
        yield 'with region' => ['en-GB'];
        yield 'three letters' => ['eng'];
        yield 'with whitespace' => [' it'];
    }

    public function testAllNamesAreListedWithTheirCodes(): void
    {
        $names = LanguageCodes::names();

        $this->assertSame('Italiano (it)', $names['it']);
        $this->assertSame('English (en)', $names['en']);
        $this->assertGreaterThan(50, count($names));
    }

    public function testEveryListedCodeIsAValidCode(): void
    {
        foreach (array_keys(LanguageCodes::names()) as $code) {
            $this->assertTrue(LanguageCodes::hasCode($code), $code);
            $this->assertNotSame('', LanguageCodes::codeToName($code), $code);
            $this->assertNotSame('', LanguageCodes::codeToNativeName($code), $code);
        }
    }

    public function testCodesAreTwoLowercaseLetters(): void
    {
        foreach (array_keys(LanguageCodes::names()) as $code) {
            $this->assertMatchesRegularExpression('/^[a-z]{2}$/', $code);
        }
    }

    public function testNamesCanBeFilteredByContinent(): void
    {
        $europe = LanguageCodes::names('EU');
        $africa = LanguageCodes::names('AF');

        $this->assertArrayHasKey('it', $europe);
        $this->assertArrayNotHasKey('zu', $europe);
        $this->assertArrayHasKey('zu', $africa);
        $this->assertArrayNotHasKey('it', $africa);
    }

    public function testLanguagesSpokenOnSeveralContinentsAppearInEachOne(): void
    {
        foreach (['AF', 'AS', 'EU', 'NA', 'OC', 'SA'] as $continent) {
            $this->assertArrayHasKey('en', LanguageCodes::names($continent), $continent);
        }
    }

    public function testFilteringByEveryContinentReturnsASubset(): void
    {
        $all = LanguageCodes::names();

        foreach (['AF', 'AS', 'EU', 'NA', 'OC', 'SA'] as $continent) {
            $filtered = LanguageCodes::names($continent);
            $this->assertNotSame([], $filtered, $continent);
            $this->assertSame([], array_diff_key($filtered, $all), $continent);
        }
    }

    public function testUnknownContinentsMatchNothing(): void
    {
        $this->assertSame([], LanguageCodes::names('XX'));
        $this->assertSame([], LanguageCodes::names(''));
    }

    public function testContinentFilterIsCaseSensitive(): void
    {
        $this->assertSame([], LanguageCodes::names('eu'));
    }
}
