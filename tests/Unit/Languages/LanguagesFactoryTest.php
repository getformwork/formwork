<?php

namespace Formwork\Tests\Unit\Languages;

use Formwork\Http\Request;
use Formwork\Languages\Languages;
use Formwork\Languages\LanguagesFactory;
use Formwork\Services\Container;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(LanguagesFactory::class)]
final class LanguagesFactoryTest extends TestCase
{
    public function testFactoryBuildsLanguages(): void
    {
        $languages = $this->make('/', null, ['available' => ['en', 'it'], 'httpPreferred' => false]);

        $this->assertInstanceOf(Languages::class, $languages);
        $this->assertSame(['en', 'it'], $languages->available()->keys());
    }

    public function testDefaultLanguageIsTheFirstAvailableOne(): void
    {
        $languages = $this->make('/', null, ['available' => ['it', 'en'], 'httpPreferred' => false]);

        $this->assertSame('it', $languages->default()?->code());
        $this->assertSame('it', $languages->current()?->code());
    }

    public function testDefaultLanguageCanBeConfigured(): void
    {
        $languages = $this->make('/', null, ['available' => ['it', 'en'], 'default' => 'en', 'httpPreferred' => false]);

        $this->assertSame('en', $languages->default()?->code());
    }

    public function testNoAvailableLanguagesProduceNoDefault(): void
    {
        $languages = $this->make('/', null, ['available' => [], 'httpPreferred' => false]);

        $this->assertNull($languages->default());
        $this->assertNull($languages->current());
    }

    #[DataProvider('requestedLanguageProvider')]
    public function testLanguageIsRequestedThroughTheUriPrefix(string $uri, ?string $expected): void
    {
        $languages = $this->make($uri, null, ['available' => ['en', 'it', 'de'], 'default' => 'en', 'httpPreferred' => false]);

        $this->assertSame($expected, $languages->requested()?->code());
        $this->assertSame($expected ?? 'en', $languages->current()?->code());
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function requestedLanguageProvider(): iterable
    {
        yield 'language home' => ['/it/', 'it'];
        yield 'language page' => ['/it/blog/post/', 'it'];
        yield 'another language' => ['/de/page', 'de'];
        yield 'default language prefix' => ['/en/page', 'en'];
        yield 'no prefix' => ['/blog/', null];
        yield 'root' => ['/', null];
        yield 'language-like page name' => ['/italian/', null];
        yield 'language in the middle' => ['/blog/it/', null];
        yield 'unavailable language' => ['/fr/page', null];
        yield 'uppercase language' => ['/IT/page', 'it'];
    }

    public function testRegionalCodesAreMatchedBeforeTheirPrefix(): void
    {
        $languages = $this->make('/en-gb/page', null, ['available' => ['en', 'en-gb'], 'default' => 'en', 'httpPreferred' => false]);

        $this->assertSame('en-gb', $languages->requested()?->code());
    }

    public function testPreferredLanguageIsTakenFromTheAcceptLanguageHeader(): void
    {
        $languages = $this->make('/', 'de;q=0.9, it;q=0.8, en;q=0.1', ['available' => ['en', 'it'], 'default' => 'en', 'httpPreferred' => true]);

        $this->assertSame('it', $languages->preferred()?->code());
    }

    public function testHighestQualityAvailableLanguageIsPreferred(): void
    {
        $languages = $this->make('/', 'en;q=0.5, it;q=0.9', ['available' => ['en', 'it'], 'default' => 'en', 'httpPreferred' => true]);

        $this->assertSame('it', $languages->preferred()?->code());
    }

    public function testPreferredLanguageIsIgnoredWhenDisabled(): void
    {
        $languages = $this->make('/', 'it', ['available' => ['en', 'it'], 'default' => 'en', 'httpPreferred' => false]);

        $this->assertNull($languages->preferred());
    }

    public function testNoPreferredLanguageWhenNoneIsAvailable(): void
    {
        $languages = $this->make('/', 'de, fr', ['available' => ['en', 'it'], 'default' => 'en', 'httpPreferred' => true]);

        $this->assertNull($languages->preferred());
    }

    public function testWildcardAcceptLanguageDoesNotChoosePreferredLanguage(): void
    {
        $languages = $this->make('/', null, ['available' => ['en', 'it'], 'default' => 'en', 'httpPreferred' => true]);

        $this->assertNull($languages->preferred());
    }

    public function testRegionalAcceptLanguageMatchesTheBaseLanguage(): void
    {
        $languages = $this->make('/', 'it-IT, en-US;q=0.5', ['available' => ['en', 'it'], 'default' => 'en', 'httpPreferred' => true]);

        $this->assertSame('it', $languages->preferred()?->code());
    }

    public function testRequestedLanguageDoesNotDependOnThePreferredOne(): void
    {
        $languages = $this->make('/de/page', 'it', ['available' => ['en', 'it', 'de'], 'default' => 'en', 'httpPreferred' => true]);

        $this->assertSame('de', $languages->requested()?->code());
        $this->assertSame('it', $languages->preferred()?->code());
        $this->assertSame('de', $languages->current()?->code());
    }

    public function testRegexMetacharactersInLanguageCodesAreLiteral(): void
    {
        $languages = $this->make('/zhXhans/page/', null, ['available' => ['en', 'zh.hans'], 'default' => 'en', 'httpPreferred' => false]);

        $this->assertNull($languages->requested());
        $this->assertSame('en', $languages->current()?->code());
    }

    public function testLanguageCodesWithMetacharactersAreMatchedLiterally(): void
    {
        $languages = $this->make('/zh.hans/page/', null, ['available' => ['en', 'zh.hans'], 'default' => 'en', 'httpPreferred' => false]);

        $this->assertSame('zh.hans', $languages->requested()?->code());
    }

    /**
     * @param array{available: list<string>, httpPreferred: bool, default?: string} $config
     */
    private function make(string $uri, ?string $acceptLanguage, array $config): Languages
    {
        $server = array_filter([
            'REQUEST_METHOD'       => 'GET',
            'SERVER_NAME'          => 'localhost',
            'SERVER_PORT'          => '80',
            'REQUEST_URI'          => $uri,
            'HTTP_ACCEPT_LANGUAGE' => $acceptLanguage,
        ], static fn(?string $value): bool => $value !== null);

        $request = new Request([], [], [], [], $server);

        return (new LanguagesFactory(new Container(), $request))->make($config);
    }
}
