<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Config\Config;
use Formwork\Pages\PageCollectionFactory;
use Formwork\Pages\PageFactory;
use Formwork\Services\Container;
use Formwork\Services\Loaders\SiteServiceLoader;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SiteServiceLoader::class)]
final class SiteServiceLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        FileSystem::createDirectory(TESTS_TMP_PATH . '/content/about', recursive: true);
        FileSystem::write(TESTS_TMP_PATH . '/content/about/page.md', "---\ntitle: About\n---\nAbout content\n");
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testSiteIsBuiltFromTheSiteConfigurationAndTheContentPath(): void
    {
        $site = $this->container(['title' => 'My site', 'routeAliases' => ['/old/' => '/about/']])->get(Site::class);

        $this->assertSame('My site', $site->get('title'));
        $this->assertSame('/about/', $site->resolveRouteAlias('old'));
        $this->assertSame(TESTS_TMP_PATH . '/content', rtrim((string) $site->contentPath(), '/'));
    }

    public function testContentPathCannotBeOverriddenByTheSiteConfiguration(): void
    {
        $site = $this->container(['title' => 'My site', 'contentPath' => '/etc'])->get(Site::class);

        $this->assertSame(TESTS_TMP_PATH . '/content', rtrim((string) $site->contentPath(), '/'));
    }

    public function testPagesOfTheContentPathAreAvailable(): void
    {
        $site = $this->container(['title' => 'My site'])->get(Site::class);

        $this->assertNotNull($site->findPage('/about'));
    }

    public function testSiteIsResolvedOnlyOnce(): void
    {
        $container = $this->container(['title' => 'My site']);

        $this->assertSame($container->get(Site::class), $container->get(Site::class));
    }

    /**
     * @param array<string, mixed> $site
     */
    private function container(array $site): Container
    {
        $config = new Config(['system' => ['charset' => 'utf-8', 'pages' => ['path' => TESTS_TMP_PATH . '/content']], 'site' => $site], resolved: true);

        $container = new Container();
        $container->define(Container::class, $container);
        $container->define(Config::class, $config);
        $container->define(PageFactory::class, App::instance()->getService(PageFactory::class));
        $container->define(PageCollectionFactory::class, App::instance()->getService(PageCollectionFactory::class));
        $container->define(Site::class)->loader(SiteServiceLoader::class);

        return $container;
    }
}
