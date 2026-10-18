<?php

namespace Formwork\Tests\Unit\Pages;

use Formwork\Cms\App;
use Formwork\Pages\ContentFile;
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
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Pages\Fixtures\BuildsPageSites;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

#[CoversClass(PageAfterDeleteEvent::class)]
#[CoversClass(PageAfterDuplicateEvent::class)]
#[CoversClass(PageAfterSaveEvent::class)]
#[CoversClass(PageBeforeDeleteEvent::class)]
#[CoversClass(PageBeforeDuplicateEvent::class)]
#[CoversClass(PageBeforeSaveEvent::class)]
#[CoversClass(PageLoadedEvent::class)]
#[CoversClass(PageOutputEvent::class)]
#[CoversClass(PageRenderEvent::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class PageEventsTest extends TestCase
{
    use BuildsPageSites;

    private App $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = App::instance();
    }

    public function testPageAfterDeleteEvent(): void
    {
        $pageAfterDeleteEvent = new PageAfterDeleteEvent($page = $this->page('/about'));

        $this->assertSame('pageAfterDelete', $pageAfterDeleteEvent->name());
        $this->assertSame($page, $pageAfterDeleteEvent->page());
    }

    public function testPageAfterDuplicateEvent(): void
    {
        $pageAfterDuplicateEvent = new PageAfterDuplicateEvent(
            $page = $this->page('/about'),
            $duplicate = $this->page('/blog'),
        );

        $this->assertSame('pageAfterDuplicate', $pageAfterDuplicateEvent->name());
        $this->assertSame($page, $pageAfterDuplicateEvent->page());
        $this->assertSame($duplicate, $pageAfterDuplicateEvent->duplicatePage());
    }

    public function testPageAfterSaveEvent(): void
    {
        $pageAfterSaveEvent = new PageAfterSaveEvent($page = $this->page('/about'));

        $this->assertSame('pageAfterSave', $pageAfterSaveEvent->name());
        $this->assertSame($page, $pageAfterSaveEvent->page());
    }

    public function testPageBeforeDeleteEvent(): void
    {
        $pageBeforeDeleteEvent = new PageBeforeDeleteEvent($page = $this->page('/about'));

        $this->assertSame('pageBeforeDelete', $pageBeforeDeleteEvent->name());
        $this->assertSame($page, $pageBeforeDeleteEvent->page());
    }

    public function testPageBeforeDuplicateEvent(): void
    {
        $with = ['title' => 'Copy'];
        $pageBeforeDuplicateEvent = new PageBeforeDuplicateEvent($page = $this->page('/about'), $with);

        $this->assertSame('pageBeforeDuplicate', $pageBeforeDuplicateEvent->name());
        $this->assertSame($page, $pageBeforeDuplicateEvent->page());

        $eventWith = &$pageBeforeDuplicateEvent->with();
        $eventWith['title'] = 'Changed';
        $this->assertSame('Changed', $with['title']);
    }

    public function testPageBeforeSaveEvent(): void
    {
        $pageBeforeSaveEvent = new PageBeforeSaveEvent($page = $this->page('/about'));

        $this->assertSame('pageBeforeSave', $pageBeforeSaveEvent->name());
        $this->assertSame($page, $pageBeforeSaveEvent->page());
    }

    public function testPageLoadedEvent(): void
    {
        $pageLoadedEvent = new PageLoadedEvent($page = $this->page('/about'));

        $this->assertSame('pageLoaded', $pageLoadedEvent->name());
        $this->assertSame($page, $pageLoadedEvent->page());
    }

    public function testPageOutputEvent(): void
    {
        $output = '<p>Before</p>';
        $pageOutputEvent = new PageOutputEvent($page = $this->page('/about'), $output);

        $this->assertSame('pageOutput', $pageOutputEvent->name());
        $this->assertSame($page, $pageOutputEvent->page());

        $eventOutput = &$pageOutputEvent->output();
        $eventOutput .= ' after';
        $this->assertSame('<p>Before</p> after', $output);
    }

    public function testPageRenderEvent(): void
    {
        $vars = ['page' => $this->page('/about')];
        $pageRenderEvent = new PageRenderEvent($vars['page'], $vars);

        $this->assertSame('pageRender', $pageRenderEvent->name());
        $this->assertSame($vars['page'], $pageRenderEvent->page());

        $eventVars = &$pageRenderEvent->vars();
        $eventVars['extra'] = true;
        $this->assertTrue($vars['extra']);
    }

    public function testBeforeSaveRunsBeforeAfterSaveAndPersistence(): void
    {
        $page = $this->page('/about');
        $events = [];
        $this->app->events()->on('pageBeforeSave', function (PageBeforeSaveEvent $pageBeforeSaveEvent) use (&$events, $page): void {
            if ($pageBeforeSaveEvent->page() === $page) {
                $events[] = 'before';
            }
        });
        $this->app->events()->on('pageAfterSave', function (PageAfterSaveEvent $pageAfterSaveEvent) use (&$events, $page): void {
            if ($pageAfterSaveEvent->page() === $page) {
                $events[] = 'after';
            }
        });

        $page->save();

        $this->assertSame(['before', 'after'], $events);
    }

    public function testBeforeSaveFailurePreventsPersistenceAndAfterSave(): void
    {
        $page = $this->page('/about');
        $page->set('title', 'Not persisted');
        $afterCalled = false;
        $this->app->events()->on('pageBeforeSave', function (PageBeforeSaveEvent $pageBeforeSaveEvent) use ($page): void {
            if ($pageBeforeSaveEvent->page() === $page) {
                throw new RuntimeException('blocked');
            }
        });
        $this->app->events()->on('pageAfterSave', function (PageAfterSaveEvent $pageAfterSaveEvent) use (&$afterCalled, $page): void {
            if ($pageAfterSaveEvent->page() === $page) {
                $afterCalled = true;
            }
        });

        $runtimeException = $this->captureException(RuntimeException::class, static fn() => $page->save());
        $this->assertSame('blocked', $runtimeException->getMessage());

        $page->reload();
        $this->assertSame('About', $page->title());
        $this->assertFalse($afterCalled);
    }

    public function testBeforeDeleteFailureLeavesThePageOnDisk(): void
    {
        $page = $this->page('/about');
        $path = $page->contentPath();
        $this->app->events()->on('pageBeforeDelete', function (PageBeforeDeleteEvent $pageBeforeDeleteEvent) use ($page): void {
            if ($pageBeforeDeleteEvent->page() === $page) {
                throw new RuntimeException('blocked');
            }
        });

        $runtimeException = $this->captureException(RuntimeException::class, static fn() => $page->delete());
        $this->assertSame('blocked', $runtimeException->getMessage());

        $this->assertDirectoryExists((string) $path);
    }

    public function testBeforeDuplicateReceivesTheOverrideDataByReference(): void
    {
        $page = $this->page('/about');
        $this->app->events()->on('pageBeforeDuplicate', function (PageBeforeDuplicateEvent $pageBeforeDuplicateEvent) use ($page): void {
            if ($pageBeforeDuplicateEvent->page() === $page) {
                $with = &$pageBeforeDuplicateEvent->with();
                $with['title'] = 'From listener';
            }
        });

        $duplicate = $page->duplicate(['title' => 'Original override']);

        $this->assertSame('From listener', $duplicate->title());
    }

    public function testAfterSaveFailureDoesNotUndoCompletedPersistence(): void
    {
        $page = $this->page('/about');
        $page->set('title', 'Persisted');
        $this->app->events()->on('pageAfterSave', function (PageAfterSaveEvent $pageAfterSaveEvent) use ($page): void {
            if ($pageAfterSaveEvent->page() === $page) {
                throw new RuntimeException('after failure');
            }
        });

        $runtimeException = $this->captureException(RuntimeException::class, static fn() => $page->save());
        $this->assertSame('after failure', $runtimeException->getMessage());

        $page->reload();
        $this->assertSame('Persisted', $page->title());
    }

    public function testSaveEventsAreDispatchedBeforeAndAfterThePageIsWritten(): void
    {
        $page = $this->page('/about');
        $file = (string) $page->contentFile()?->path();
        $titles = [];

        $this->app->events()->on('pageBeforeSave', function (PageBeforeSaveEvent $pageBeforeSaveEvent) use (&$titles, $page, $file): void {
            if ($pageBeforeSaveEvent->page() === $page) {
                $titles['before'] = (new ContentFile($file))->frontmatter()['title'];
                $page->set('title', 'Changed by listener');
            }
        });

        $this->app->events()->on('pageAfterSave', function (PageAfterSaveEvent $pageAfterSaveEvent) use (&$titles, $page, $file): void {
            if ($pageAfterSaveEvent->page() === $page) {
                $titles['after'] = (new ContentFile($file))->frontmatter()['title'];
            }
        });

        $page->save();

        $this->assertSame(['before' => 'About', 'after' => 'Changed by listener'], $titles);
    }

    private function page(string $route): Page
    {
        $site = $this->siteFromFixture();
        $path = FileSystem::joinPaths((string) $site->contentPath(), trim($route, '/')) . '/';

        return new Page(['site' => $site, 'path' => $path], $this->app);
    }
}
