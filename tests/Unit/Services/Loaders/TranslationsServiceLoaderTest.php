<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Services\Container;
use Formwork\Services\Loaders\TranslationsServiceLoader;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translations;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TranslationsServiceLoader::class)]
final class TranslationsServiceLoaderTest extends TestCase
{
    private string $systemPath;

    private string $sitePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->systemPath = TESTS_TMP_PATH . '/translations/system';
        $this->sitePath = TESTS_TMP_PATH . '/translations/site';
        FileSystem::createDirectory($this->systemPath, recursive: true);
        FileSystem::write($this->systemPath . '/en.yaml', "greeting: Hello\nsystem.only: System\n");
        FileSystem::write($this->systemPath . '/it.yaml', "greeting: Ciao\n");
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testSystemTranslationsAreLoaded(): void
    {
        $translations = $this->resolve();

        $this->assertTrue($translations->has('en'));
        $this->assertTrue($translations->has('it'));
        $this->assertSame('Ciao', $translations->get('it')->translate('greeting'));
    }

    public function testSiteTranslationsAreOptional(): void
    {
        $this->assertFalse(FileSystem::isDirectory($this->sitePath, assertExists: false));

        $this->assertCount(2, $this->resolve()->getAll());
    }

    public function testSiteTranslationsExtendAndOverrideTheSystemOnes(): void
    {
        FileSystem::createDirectory($this->sitePath, recursive: true);
        FileSystem::write($this->sitePath . '/en.yaml', "greeting: Welcome\nsite.only: Site\n");
        FileSystem::write($this->sitePath . '/fr.yaml', "greeting: Bonjour\n");

        $translations = $this->resolve();

        $english = $translations->get('en');
        $this->assertSame('Welcome', $english->translate('greeting'));
        $this->assertSame('System', $english->translate('system.only'));
        $this->assertSame('Site', $english->translate('site.only'));
        $this->assertTrue($translations->has('fr'));
    }

    public function testTranslationsAreResolvedOnlyOnce(): void
    {
        $container = $this->container();

        $this->assertSame($container->get(Translations::class), $container->get(Translations::class));
    }

    private function resolve(): Translations
    {
        return $this->container()->get(Translations::class);
    }

    private function container(): Container
    {
        $config = new Config(['system' => ['translations' => [
            'fallback' => 'en',
            'paths'    => ['system' => $this->systemPath, 'site' => $this->sitePath],
        ]]], resolved: true);

        $container = new Container();
        $container->define(Container::class, $container);
        $container->define(Config::class, $config);
        $container->define(Translations::class)->loader(TranslationsServiceLoader::class);

        return $container;
    }
}
