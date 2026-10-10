<?php

namespace Formwork\Tests\Unit\Pages;

use Formwork\Cms\App;
use Formwork\Cms\Site;
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

    public function testBeforeSaveRunsBeforeAfterSaveAndPersistence(): void
    {
        $page = $this->page('/about');
        $events = [];
        $this->app->events()->on('pageBeforeSave', function (object $event) use (&$events, $page): void {
            if ($event->page() === $page) {
                $events[] = 'before';
            }
        });
        $this->app->events()->on('pageAfterSave', function (object $event) use (&$events, $page): void {
            if ($event->page() === $page) {
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
        $this->app->events()->on('pageBeforeSave', function (object $event) use ($page): void {
            if ($event->page() === $page) {
                throw new \RuntimeException('blocked');
            }
        });
        $this->app->events()->on('pageAfterSave', function (object $event) use (&$afterCalled, $page): void {
            if ($event->page() === $page) {
                $afterCalled = true;
            }
        });

        try {
            $page->save();
            $this->fail('The before-save exception should have propagated.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('blocked', $exception->getMessage());
        }

        $page->reload();
        $this->assertSame('About', $page->title());
        $this->assertFalse($afterCalled);
    }

    public function testBeforeDeleteFailureLeavesThePageOnDisk(): void
    {
        $page = $this->page('/about');
        $path = $page->contentPath();
        $this->app->events()->on('pageBeforeDelete', function (object $event) use ($page): void {
            if ($event->page() === $page) {
                throw new \RuntimeException('blocked');
            }
        });

        try {
            $page->delete();
            $this->fail('The before-delete exception should have propagated.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('blocked', $exception->getMessage());
        }

        $this->assertDirectoryExists((string) $path);
    }

    public function testBeforeDuplicateReceivesTheOverrideDataByReference(): void
    {
        $page = $this->page('/about');
        $this->app->events()->on('pageBeforeDuplicate', function (object $event) use ($page): void {
            if ($event->page() === $page) {
                $with = &$event->with();
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
        $this->app->events()->on('pageAfterSave', function (object $event) use ($page): void {
            if ($event->page() === $page) {
                throw new \RuntimeException('after failure');
            }
        });

        try {
            $page->save();
            $this->fail('The after-save exception should have propagated.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('after failure', $exception->getMessage());
        }

        $page->reload();
        $this->assertSame('Persisted', $page->title());
    }

    public function testSaveEventsAreDispatchedBeforeAndAfterThePageIsWritten(): void
    {
        $page = $this->page('/about');
        $file = (string) $page->contentFile()?->path();
        $active = true;
        $titles = [];

        $this->app->events()->on('pageBeforeSave', function (PageBeforeSaveEvent $event) use (&$active, &$titles, $page, $file): void {
            if ($active && $event->page() === $page) {
                $titles['before'] = (new ContentFile($file))->frontmatter()['title'];
                $page->set('title', 'Changed by listener');
            }
        });
        $this->app->events()->on('pageAfterSave', function (PageAfterSaveEvent $event) use (&$active, &$titles, $page, $file): void {
            if ($active && $event->page() === $page) {
                $titles['after'] = (new ContentFile($file))->frontmatter()['title'];
            }
        });

        try {
            $page->save();
        } finally {
            $active = false;
        }

        $this->assertSame(['before' => 'About', 'after' => 'Changed by listener'], $titles);
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
        FileSystem::copyDirectory(__DIR__ . '/Fixtures/site', $path);

        return new Site(
            ['path' => TESTS_TMP_PATH, 'contentPath' => $path, 'metadata' => []],
            $this->app->config(),
            $this->app->getService(PageFactory::class),
            $this->app->getService(PageCollectionFactory::class),
        );
    }
}
