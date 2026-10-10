<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Http\Request;
use Formwork\Languages\Languages;
use Formwork\Languages\LanguagesFactory;
use Formwork\Services\Container;
use Formwork\Services\Loaders\LanguagesServiceLoader;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translations;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(LanguagesServiceLoader::class)]
final class LanguagesServiceLoaderTest extends TestCase
{
    private Translations $translations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        FileSystem::createDirectory(TESTS_TMP_PATH . '/translations');
        foreach (['en' => 'Hello', 'it' => 'Ciao', 'fr' => 'Bonjour'] as $code => $greeting) {
            FileSystem::write(TESTS_TMP_PATH . "/translations/{$code}.yaml", "greeting: {$greeting}\n");
        }
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testDefaultLanguageBecomesTheCurrentTranslation(): void
    {
        $languages = $this->load(['available' => ['it', 'en'], 'httpPreferred' => false]);

        $this->assertSame('it', $languages->default()?->code());
        $this->assertSame('Ciao', $this->translations->getCurrent()->translate('greeting'));
    }

    public function testRequestedLanguageBecomesTheCurrentTranslation(): void
    {
        $this->load(['available' => ['en', 'it'], 'httpPreferred' => false], uri: '/it/page/');

        $this->assertSame('Ciao', $this->translations->getCurrent()->translate('greeting'));
    }

    public function testConfiguredDefaultWinsOverTheFirstAvailableLanguage(): void
    {
        $languages = $this->load(['available' => ['en', 'it'], 'default' => 'it', 'httpPreferred' => false]);

        $this->assertSame('it', $languages->default()?->code());
        $this->assertSame('Ciao', $this->translations->getCurrent()->translate('greeting'));
    }

    public function testSiteWithoutLanguagesKeepsTheFallbackTranslation(): void
    {
        $languages = $this->load(['available' => [], 'httpPreferred' => false]);

        $this->assertNull($languages->default());
        $this->assertSame('Hello', $this->translations->getCurrent()->translate('greeting'));
    }

    public function testLanguagesNotShippedAsTranslationsFallBackToTheDefaultTranslation(): void
    {
        $this->load(['available' => ['de'], 'httpPreferred' => false]);

        $this->assertSame('Hello', $this->translations->getCurrent()->translate('greeting'));
    }

    /**
     * @param array{available: list<string>, httpPreferred: bool, default?: string} $siteLanguages
     */
    private function load(array $siteLanguages, string $uri = '/'): Languages
    {
        $config = new Config([
            'system' => ['translations' => ['fallback' => 'en']],
            'site'   => ['languages' => $siteLanguages],
        ], resolved: true);

        $this->translations = new Translations($config);
        $this->translations->loadFromPath(TESTS_TMP_PATH . '/translations');

        $request = new Request([], [], [], [], ['REQUEST_URI' => $uri, 'SCRIPT_NAME' => '/index.php', 'REQUEST_METHOD' => 'GET']);

        $container = new Container();
        $container->define(Container::class, $container);
        $container->define(Request::class, $request);
        $factory = new LanguagesFactory($container, $request);

        return (new LanguagesServiceLoader($config, $this->translations, $factory))->load($container);
    }
}
