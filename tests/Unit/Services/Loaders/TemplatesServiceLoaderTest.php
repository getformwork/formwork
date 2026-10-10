<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Config\Config;
use Formwork\Services\Container;
use Formwork\Services\Loaders\TemplatesServiceLoader;
use Formwork\Templates\Template;
use Formwork\Templates\TemplateFactory;
use Formwork\Templates\Templates;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use Formwork\View\ViewFactory;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TemplatesServiceLoader::class)]
final class TemplatesServiceLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        FileSystem::createDirectory(TESTS_TMP_PATH . '/templates/assets', recursive: true);
        FileSystem::createDirectory(TESTS_TMP_PATH . '/templates/partials', recursive: true);
        FileSystem::write(TESTS_TMP_PATH . '/templates/page.php', 'page');
        FileSystem::write(TESTS_TMP_PATH . '/templates/post.php', 'post');
        FileSystem::write(TESTS_TMP_PATH . '/templates/readme.txt', 'not a template');
        FileSystem::write(TESTS_TMP_PATH . '/templates/partials/header.php', 'partial');
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testEveryPhpFileOfTheTemplatesDirectoryIsATemplate(): void
    {
        $templates = $this->load();

        $this->assertInstanceOf(Templates::class, $templates);
        $this->assertEqualsCanonicalizing(['page', 'post'], $templates->keys());
        $this->assertContainsOnlyInstancesOf(Template::class, $templates);
    }

    public function testTemplatesKnowTheirNameAndScheme(): void
    {
        $templates = $this->load();

        $this->assertSame('post', $templates->get('post')->name());
        $this->assertSame('pages.post', $templates->get('post')->scheme()->id());
    }

    public function testTemplatesWithoutSchemeAreReportedClearly(): void
    {
        FileSystem::write(TESTS_TMP_PATH . '/templates/orphan.php', 'orphan');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pages.orphan');

        $this->load();
    }

    public function testNestedTemplatesAreNotListed(): void
    {
        $this->assertNotContains('header', $this->load()->keys());
    }

    private function load(): Templates
    {
        $config = new Config(['system' => ['templates' => ['path' => TESTS_TMP_PATH . '/templates']]], resolved: true);

        $container = new Container();
        $container->define(Container::class, $container);
        $container->define(Site::class, App::instance()->site());
        $container->define(ViewFactory::class, App::instance()->getService(ViewFactory::class));

        $factory = new TemplateFactory($container, App::instance(), $config, App::instance()->schemes());

        return (new TemplatesServiceLoader($config, $factory))->load($container);
    }
}
