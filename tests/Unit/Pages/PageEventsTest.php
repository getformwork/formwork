<?php

namespace Formwork\Tests\Unit\Pages;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Pages\Events\PageAfterDeleteEvent;
use Formwork\Pages\Events\PageAfterDuplicateEvent;
use Formwork\Pages\Events\PageAfterSaveEvent;
use Formwork\Pages\Events\PageBeforeDeleteEvent;
use Formwork\Pages\Events\PageBeforeDuplicateEvent;
use Formwork\Pages\Events\PageBeforeSaveEvent;
use Formwork\Pages\Events\PageLoadedEvent;
use Formwork\Pages\Events\PageOutputEvent;
use Formwork\Pages\Events\PageRenderEvent;
use Formwork\Pages\Page;
use Formwork\Pages\PageCollectionFactory;
use Formwork\Pages\PageFactory;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PageAfterDeleteEvent::class)]
#[CoversClass(PageAfterDuplicateEvent::class)]
#[CoversClass(PageAfterSaveEvent::class)]
#[CoversClass(PageBeforeDeleteEvent::class)]
#[CoversClass(PageBeforeDuplicateEvent::class)]
#[CoversClass(PageBeforeSaveEvent::class)]
#[CoversClass(PageLoadedEvent::class)]
#[CoversClass(PageOutputEvent::class)]
#[CoversClass(PageRenderEvent::class)]
final class PageEventsTest extends TestCase
{
    private App $app;

    private int $temporarySiteCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->app = App::instance();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testPageAfterDeleteEvent(): void
    {
        $event = new PageAfterDeleteEvent($page = $this->page('/about'));

        $this->assertSame('pageAfterDelete', $event->name());
        $this->assertSame($page, $event->page());
    }

    public function testPageAfterDuplicateEvent(): void
    {
        $event = new PageAfterDuplicateEvent(
            $page = $this->page('/about'),
            $duplicate = $this->page('/blog'),
        );

        $this->assertSame('pageAfterDuplicate', $event->name());
        $this->assertSame($page, $event->page());
        $this->assertSame($duplicate, $event->duplicatePage());
    }

    public function testPageAfterSaveEvent(): void
    {
        $event = new PageAfterSaveEvent($page = $this->page('/about'));

        $this->assertSame('pageAfterSave', $event->name());
        $this->assertSame($page, $event->page());
    }

    public function testPageBeforeDeleteEvent(): void
    {
        $event = new PageBeforeDeleteEvent($page = $this->page('/about'));

        $this->assertSame('pageBeforeDelete', $event->name());
        $this->assertSame($page, $event->page());
    }

    public function testPageBeforeDuplicateEvent(): void
    {
        $with = ['title' => 'Copy'];
        $event = new PageBeforeDuplicateEvent($page = $this->page('/about'), $with);

        $this->assertSame('pageBeforeDuplicate', $event->name());
        $this->assertSame($page, $event->page());

        $eventWith = &$event->with();
        $eventWith['title'] = 'Changed';
        $this->assertSame('Changed', $with['title']);
    }

    public function testPageBeforeSaveEvent(): void
    {
        $event = new PageBeforeSaveEvent($page = $this->page('/about'));

        $this->assertSame('pageBeforeSave', $event->name());
        $this->assertSame($page, $event->page());
    }

    public function testPageLoadedEvent(): void
    {
        $event = new PageLoadedEvent($page = $this->page('/about'));

        $this->assertSame('pageLoaded', $event->name());
        $this->assertSame($page, $event->page());
    }

    public function testPageOutputEvent(): void
    {
        $output = '<p>Before</p>';
        $event = new PageOutputEvent($page = $this->page('/about'), $output);

        $this->assertSame('pageOutput', $event->name());
        $this->assertSame($page, $event->page());

        $eventOutput = &$event->output();
        $eventOutput .= ' after';
        $this->assertSame('<p>Before</p> after', $output);
    }

    public function testPageRenderEvent(): void
    {
        $vars = ['page' => $this->page('/about')];
        $event = new PageRenderEvent($vars['page'], $vars);

        $this->assertSame('pageRender', $event->name());
        $this->assertSame($vars['page'], $event->page());

        $eventVars = &$event->vars();
        $eventVars['extra'] = true;
        $this->assertTrue($vars['extra']);
    }

    private function page(string $route): Page
    {
        $site = $this->temporarySite();
        $path = FileSystem::joinPaths((string) $site->contentPath(), trim($route, '/')) . '/';

        return new Page(['site' => $site, 'path' => $path], $this->app);
    }

    private function temporarySite(): Site
    {
        $path = FileSystem::joinPaths(TESTS_TMP_PATH, 'page-events-' . ++$this->temporarySiteCounter);
        FileSystem::copyDirectory(__DIR__ . '/fixtures/site', $path);

        return new Site(
            ['path' => TESTS_TMP_PATH, 'contentPath' => $path, 'metadata' => []],
            $this->app->config(),
            $this->app->getService(PageFactory::class),
            $this->app->getService(PageCollectionFactory::class),
        );
    }
}
