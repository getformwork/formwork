<?php

namespace Formwork\Tests\Unit\Translations;

use Formwork\Config\Config;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translation;
use Formwork\Translations\Translations;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Translations::class)]
final class TranslationsTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->path = FileSystem::joinPaths(TESTS_TMP_PATH, 'translations');
        FileSystem::createDirectory($this->path);
        $this->write('en', "hello: Hello\ngreeting: 'Hi %s'\nonly.english: English only\nplural: [one, many]\n");
        $this->write('it', "hello: Ciao\ngreeting: 'Ciao %s'\n");
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testNothingIsAvailableBeforeLoading(): void
    {
        $translations = new Translations($this->config('en'));

        $this->assertFalse($translations->has('en'));
    }

    public function testFilesAreLoadedByName(): void
    {
        $translations = new Translations($this->config('en'));

        $translations->load($this->path . '/en.yaml');

        $this->assertTrue($translations->has('en'));
        $this->assertFalse($translations->has('it'));
        $this->assertSame('Hello', $translations->get('en')->translate('hello'));
    }

    public function testWholeDirectoriesCanBeLoaded(): void
    {
        $translations = $this->translations();

        $translations->loadFromPath($this->path);

        $this->assertTrue($translations->has('en'));
        $this->assertTrue($translations->has('it'));
        $this->assertSame('Ciao', $translations->get('it')->translate('hello'));
    }

    public function testOnlyReadableYamlFilesAreLoaded(): void
    {
        FileSystem::write($this->path . '/fr.txt', 'hello: Bonjour');
        FileSystem::write($this->path . '/de.yml', 'hello: Hallo');
        $translations = new Translations($this->config('en'));

        $translations->load($this->path . '/fr.txt');
        $translations->load($this->path . '/de.yml');
        $translations->load($this->path . '/missing.yaml');

        $this->assertFalse($translations->has('fr'));
        $this->assertFalse($translations->has('de'));
        $this->assertFalse($translations->has('missing'));
    }

    public function testUnknownTranslationsAreReported(): void
    {
        $translations = $this->translations();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid translation code "fr"');
        $translations->get('fr');
    }

    public function testUnknownTranslationsCanFallBack(): void
    {
        $translations = $this->translations();

        $translation = $translations->get('fr', fallbackIfInvalid: true);

        $this->assertSame('en', $translation->code());
    }

    public function testTranslationsAreCached(): void
    {
        $translations = $this->translations();

        $this->assertSame($translations->get('it'), $translations->get('it'));
    }

    public function testTranslationsFallBackToTheConfiguredFallback(): void
    {
        $translations = $this->translations();

        $this->assertSame('English only', $translations->get('it')->translate('only.english'));
        $this->assertSame('Ciao', $translations->get('it')->translate('hello'));
    }

    public function testFallbackTranslationHasNoFallbackOfItsOwn(): void
    {
        $translations = $this->translations();

        $this->expectException(InvalidArgumentException::class);
        $translations->get('en')->translate('missing');
    }

    public function testMissingFallbackTranslationIsReported(): void
    {
        $translations = new Translations($this->config('fr'));
        $translations->loadFromPath($this->path);

        $this->expectException(InvalidArgumentException::class);
        $translations->get('it');
    }

    public function testMultipleFilesOfTheSameCodeAreMerged(): void
    {
        $extra = FileSystem::joinPaths(TESTS_TMP_PATH, 'extra');
        FileSystem::createDirectory($extra);
        FileSystem::write($extra . '/en.yaml', "hello: Overridden\nplugin.key: From plugin\n");
        $translations = new Translations($this->config('en'));

        $translations->load($this->path . '/en.yaml');
        $translations->load($extra . '/en.yaml');

        $english = $translations->get('en');
        $this->assertSame('Overridden', $english->translate('hello'), 'Later files override earlier ones');
        $this->assertSame('From plugin', $english->translate('plugin.key'));
        $this->assertSame('English only', $english->translate('only.english'));
    }

    public function testLoadingAFileResetsTheCachedTranslation(): void
    {
        $extra = FileSystem::joinPaths(TESTS_TMP_PATH, 'extra');
        FileSystem::createDirectory($extra);
        FileSystem::write($extra . '/en.yaml', "plugin.key: From plugin\n");
        $translations = $this->translations();
        $before = $translations->get('en');

        $translations->load($extra . '/en.yaml');

        $this->assertNotSame($before, $translations->get('en'));
        $this->assertSame('From plugin', $translations->get('en')->translate('plugin.key'));
    }

    public function testTranslationsLoadedBeforeNewStringsStillSeeThemThroughTheFallback(): void
    {
        $extra = FileSystem::joinPaths(TESTS_TMP_PATH, 'extra');
        FileSystem::createDirectory($extra);
        FileSystem::write($extra . '/en.yaml', "plugin.key: From plugin\n");
        $translations = $this->translations();
        $italian = $translations->get('it');

        $translations->load($extra . '/en.yaml');

        $this->assertSame('From plugin', $translations->get('it')->translate('plugin.key'));
        $this->assertSame('From plugin', $italian->translate('plugin.key'));
    }

    public function testMultipleTranslationsCanBeRetrieved(): void
    {
        $translations = $this->translations();

        $result = $translations->getMultiple(['it', 'en']);

        $this->assertSame(['it', 'en'], array_keys($result));
        $this->assertContainsOnlyInstancesOf(Translation::class, $result);
    }

    public function testMultipleTranslationsCanFallBack(): void
    {
        $translations = $this->translations();

        $result = $translations->getMultiple(['it', 'xx'], fallbackIfInvalid: true);

        $this->assertSame('en', $result['xx']->code());
    }

    public function testMultipleTranslationsFailWhenOneIsUnknown(): void
    {
        $translations = $this->translations();

        $this->expectException(InvalidArgumentException::class);
        $translations->getMultiple(['it', 'xx']);
    }

    public function testAllTranslationsCanBeRetrieved(): void
    {
        $translations = $this->translations();

        $all = $translations->getAll();

        $this->assertEqualsCanonicalizing(['en', 'it'], array_keys($all));
    }

    public function testCurrentTranslationDefaultsToTheFallback(): void
    {
        $translations = $this->translations();

        $this->assertSame('en', $translations->getCurrent()->code());
    }

    public function testCurrentTranslationCanBeChanged(): void
    {
        $translations = $this->translations();

        $translations->setCurrent('it');

        $this->assertSame('it', $translations->getCurrent()->code());
        $this->assertSame('Ciao', $translations->getCurrent()->translate('hello'));
    }

    public function testUnknownCurrentTranslationsFallBackToTheDefaultOne(): void
    {
        $translations = $this->translations();

        $translations->setCurrent('xx');

        $this->assertSame('en', $translations->getCurrent()->code());
    }

    public function testFallbackTranslationIsTheConfiguredOne(): void
    {
        $this->assertSame('en', $this->translations()->getFallback()->code());
    }

    public function testListsOfStringsAreAvailable(): void
    {
        $this->assertSame(['one', 'many'], $this->translations()->get('en')->getStrings('plural'));
    }

    public function testInvalidYamlIsReported(): void
    {
        $this->write('de', "key: [unclosed\n");
        $translations = $this->translations();

        $this->expectException(\Throwable::class);
        $translations->get('de');
    }

    private function translations(): Translations
    {
        $translations = new Translations($this->config('en'));
        $translations->loadFromPath($this->path);

        return $translations;
    }

    private function config(string $fallback): Config
    {
        return new Config(['system' => ['translations' => ['fallback' => $fallback]]], resolved: true);
    }

    private function write(string $code, string $content): void
    {
        FileSystem::write($this->path . "/{$code}.yaml", $content);
    }
}
