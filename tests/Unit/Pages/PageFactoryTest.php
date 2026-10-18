<?php

namespace Formwork\Tests\Unit\Pages;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Pages\Page;
use Formwork\Pages\PageCollection;
use Formwork\Pages\PageCollectionFactory;
use Formwork\Pages\PageFactory;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PageFactory::class)]
#[CoversClass(PageCollectionFactory::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class PageFactoryTest extends TestCase
{
    private App $app;

    private Site $fixtureSite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = App::instance();
        $path = FileSystem::joinPaths(TESTS_TMP_PATH, 'page-factory-site');
        FileSystem::copyDirectory(__DIR__ . '/Fixtures/site', $path);
        $this->fixtureSite = new Site(
            ['path' => TESTS_TMP_PATH, 'contentPath' => $path, 'metadata' => []],
            $this->app->config(),
            $this->app->getService(PageFactory::class),
            $this->app->getService(PageCollectionFactory::class),
        );
    }

    public function testFactoriesBuildTheExpectedPageObjects(): void
    {
        $pageFactory = $this->app->getService(PageFactory::class);
        $pageCollectionFactory = $this->app->getService(PageCollectionFactory::class);

        $page = $pageFactory->make(['site' => $this->fixtureSite]);
        $pageCollection = $pageCollectionFactory->make([]);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertTrue($page->isEmpty());
        $this->assertInstanceOf(PageCollection::class, $pageCollection);
        $this->assertTrue($pageCollection->isEmpty());
    }
}
