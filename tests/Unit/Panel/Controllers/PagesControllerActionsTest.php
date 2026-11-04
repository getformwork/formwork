<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Fields\Field;
use Formwork\Fields\FieldCollection;
use Formwork\Fields\FieldFactory;
use Formwork\Files\Services\FileUploader;
use Formwork\Http\JsonResponse;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Pages\PageFactory;
use Formwork\Panel\ContentHistory\ContentHistory;
use Formwork\Panel\ContentHistory\ContentHistoryEvent;
use Formwork\Panel\ContentHistory\ContentHistoryItem;
use Formwork\Panel\Controllers\PagesController;
use Formwork\Panel\Modals\Modal;
use Formwork\Panel\Modals\Modals;
use Formwork\Parsers\Json;
use Formwork\Router\RouteParams;
use Formwork\Schemes\Schemes;
use Formwork\Services\Container;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\Fixtures\BuildsPanelControllers;
use Formwork\Tests\Unit\Panel\Fixtures\CreatesUploads;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Users\User;
use Formwork\Users\UserFactory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PagesController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class PagesControllerActionsTest extends TestCase
{
    use BuildsPanelControllers;
    use CreatesUploads;

    private string $pagesPath;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $app = App::instance();

        // `FieldFactory` is registered lazily by the schemes service loader
        $app->getService(Schemes::class);

        // Each test works on its own copy of the pages, so that they can be changed freely
        $this->pagesPath = TESTS_TMP_PATH . '/pages';
        FileSystem::copyDirectory($app->config()->getString('system.pages.path'), $this->pagesPath);

        $this->site = $app->getService(Container::class)->build(Site::class, ['data' => [
            ...$app->config()->getArray('site'),
            'contentPath' => $this->pagesPath,
        ]]);
        $this->site->load();
    }

    protected function tearDown(): void
    {
        $this->closePanelSession();
        parent::tearDown();
    }

    public function testIndexRedirectsToTheTree(): void
    {
        $response = $this->controller()->index();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringEndsWith('/route/panel.pages.tree', $response->headers()->get('Location'));
    }

    public function testIndexRequiresThePermission(): void
    {
        $response = $this->controller(['panel.pages.tree' => false])->index();

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testTreeRequiresThePermission(): void
    {
        $response = $this->controller(['panel.pages.tree' => false])->tree(new RouteParams([]));

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testTreeOfTheSiteIsDisplayed(): void
    {
        $response = $this->controller()->tree(new RouteParams([]));
        $view = $this->renderedView($response);

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertSame('@panel.pages.index', $view['view']);
        $this->assertSame('@panel.pages.tree', $this->renderedView(new Response($view['vars']['pagesTree']))['view']);
    }

    public function testTreeOfAnUnknownPageIsNotFound(): void
    {
        $response = $this->controller()->tree(new RouteParams(['page' => 'missing']));

        $this->assertSame(ResponseStatus::NotFound, $response->status());
    }

    public function testTreeOfAPageWithoutChildrenIsNotFound(): void
    {
        $response = $this->controller()->tree(new RouteParams(['page' => 'about']));

        $this->assertSame(ResponseStatus::NotFound, $response->status());
    }

    public function testCreatingPagesRequiresThePermission(): void
    {
        $response = $this->controller(['panel.pages.create' => false])->create($this->pageFactory());

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testInvalidNewPagesAreNotCreated(): void
    {
        $before = $this->pageDirectories();

        $response = $this->controller(input: ['newPageModal' => ['title' => '']], server: ['REQUEST_METHOD' => 'POST'])->create($this->pageFactory());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
        $this->assertSame($before, $this->pageDirectories());
    }

    public function testDuplicatingPagesRequiresThePermission(): void
    {
        $response = $this->controller(['panel.pages.duplicate' => false])->duplicate(new RouteParams(['page' => 'about']));

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testUnknownPagesCannotBeDuplicated(): void
    {
        $response = $this->controller()->duplicate(new RouteParams(['page' => 'missing']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
    }

    public function testInvalidDuplicatesAreNotCreated(): void
    {
        $before = $this->pageDirectories();

        $response = $this->controller(input: ['duplicatePageModal' => ['title' => '']], server: ['REQUEST_METHOD' => 'POST'])->duplicate(new RouteParams(['page' => 'about']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
        $this->assertSame($before, $this->pageDirectories());
    }

    public function testEditingPagesRequiresThePermission(): void
    {
        $response = $this->controller(['panel.pages.edit' => false])->edit(new RouteParams(['page' => 'about']));

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testUnknownPagesCannotBeEdited(): void
    {
        $response = $this->controller()->edit(new RouteParams(['page' => 'missing']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
    }

    public function testEditorIsDisplayedWithTheNeighbourPages(): void
    {
        $response = $this->controller(server: ['REQUEST_METHOD' => 'GET'])->edit(new RouteParams(['page' => 'about']));
        $view = $this->renderedView($response);

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertSame('@panel.pages.editor', $view['view']);
        $this->assertSame('About', $view['vars']['page']['title']);
        $this->assertArrayHasKey('title', $view['vars']['fields']);
        $this->assertNotNull($view['vars']['previousPage']);
        $this->assertNotNull($view['vars']['nextPage']);
    }

    public function testEditorOfThePageOpensTheNewPageModalOnRequest(): void
    {
        $modal = $this->createMock(Modal::class);
        $modal->expects($this->once())->method('open')->with(true);

        $modals = $this->createStub(Modals::class);
        $modals->method('get')->willReturn($modal);

        $this->controller(services: [Modals::class => $modals], query: ['createNew' => ''], server: ['REQUEST_METHOD' => 'GET'])->edit(new RouteParams(['page' => 'about']));
    }

    public function testInvalidPagesAreNotSaved(): void
    {
        $content = $this->site->findPage('about')?->contentFile()?->path();

        $this->assertNotNull($content);

        $original = FileSystem::read($content);

        $response = $this->controller(input: ['title' => ''], server: ['REQUEST_METHOD' => 'POST'])->edit(new RouteParams(['page' => 'about']));

        $this->assertSame(ResponseStatus::UnprocessableEntity, $response->status());
        $this->assertSame($original, FileSystem::read($content));
        $this->assertSame('error', $this->lastNotificationType());
    }

    public function testPreviewRequiresThePermission(): void
    {
        $response = $this->controller(['panel.pages.preview' => false])->preview(new RouteParams(['page' => 'about']));

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testUnknownPagesCannotBePreviewed(): void
    {
        $response = $this->controller()->preview(new RouteParams(['page' => 'missing']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
    }

    public function testPagesCanBePreviewed(): void
    {
        $response = $this->controller(input: $this->pageInput('about', ['title' => 'Preview title']), server: ['REQUEST_METHOD' => 'POST'])->preview(new RouteParams(['page' => 'about']));

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertStringContainsString('About', $response->content());
    }

    public function testPreviewDoesNotChangeThePage(): void
    {
        $page = $this->site->findPage('about');

        $this->assertNotNull($page);

        $published = $page->get('published');
        $cacheable = $page->get('cacheable');

        $this->controller(input: $this->pageInput('about'), server: ['REQUEST_METHOD' => 'POST'])->preview(new RouteParams(['page' => 'about']));

        $this->assertSame($published, $page->get('published'));
        $this->assertSame($cacheable, $page->get('cacheable'));
    }

    public function testPagesCanBeEdited(): void
    {
        $page = $this->site->findPage('about');

        $this->assertNotNull($page);
        $this->assertNotNull($page->contentPath());

        $response = $this->controller(input: $this->pageInput('about', ['title' => 'About us', 'content' => 'New content']), server: ['REQUEST_METHOD' => 'POST'], user: $this->administrator())->edit(new RouteParams(['page' => 'about']));

        $this->assertInstanceOf(RedirectResponse::class, $response, $response->content());
        $this->assertStringEndsWith('/route/panel.pages.edit', $response->headers()->get('Location'));
        $this->assertSame('success', $this->lastNotificationType());
        $this->assertStringContainsString('About us', FileSystem::read((string) $page->contentFile()?->path()));
        $this->assertFileExists($page->contentPath() . '/.history');
    }

    public function testEditingARecordsTheUserInTheHistory(): void
    {
        $this->controller(input: $this->pageInput('about', ['title' => 'About us']), server: ['REQUEST_METHOD' => 'POST'], user: $this->administrator())->edit(new RouteParams(['page' => 'about']));

        $history = new ContentHistory($this->site->findPage('about')?->contentPath() ?? '');

        $this->assertInstanceOf(ContentHistoryItem::class, $item = $history->lastItem());
        $this->assertSame('tester', $item->user());
        $this->assertSame(ContentHistoryEvent::Edited, $item->event());
    }

    public function testUnchangedPagesAreNotAddedToTheHistory(): void
    {
        $this->controller(input: $this->pageInput('about'), server: ['REQUEST_METHOD' => 'POST'], user: $this->administrator())->edit(new RouteParams(['page' => 'about']));

        $this->assertFileDoesNotExist(($this->site->findPage('about')?->contentPath() ?? '') . '/.history');
    }

    public function testPagesCanBePublishedWhileEditing(): void
    {
        $page = $this->site->findPage('about');

        $this->assertNotNull($page);

        $this->controller(input: $this->pageInput('about', ['published' => '0']), query: ['publish' => '1'], server: ['REQUEST_METHOD' => 'POST'], user: $this->administrator())->edit(new RouteParams(['page' => 'about']));

        $this->assertTrue($page->get('published'));
        $this->assertFileExists(($page->contentPath() ?? '') . '/.history');
    }

    public function testNewPagesCanBeCreated(): void
    {
        $response = $this->controller(input: ['newPageModal' => $this->newPageInput()], server: ['REQUEST_METHOD' => 'POST'], user: $this->administrator())->create($this->pageFactory());

        $this->assertInstanceOf(RedirectResponse::class, $response, $response->content());
        $this->assertStringEndsWith('/route/panel.pages.edit', $response->headers()->get('Location'));
        $this->assertSame('success', $this->lastNotificationType());
        $this->assertContains('fresh', array_map(fn(string $directory): string => preg_replace('/^\d+-/', '', basename($directory)) ?? '', glob($this->pagesPath . '/*', GLOB_ONLYDIR) ?: []));
    }

    public function testNewPagesAreUnpublishedAndRecordedInTheHistory(): void
    {
        $this->controller(input: ['newPageModal' => $this->newPageInput()], server: ['REQUEST_METHOD' => 'POST'], user: $this->administrator())->create($this->pageFactory());

        $directory = glob($this->pagesPath . '/*fresh', GLOB_ONLYDIR)[0] ?? null;

        $this->assertNotNull($directory);
        $this->assertStringContainsString('published: false', FileSystem::read(glob($directory . '/*.md')[0] ?? ''));
        $this->assertTrue((new ContentHistory($directory))->isJustCreated());
    }

    public function testPagesCanBeDuplicated(): void
    {
        $response = $this->controller(input: ['duplicatePageModal' => ['title' => 'About copy', 'slug' => 'about-copy']], server: ['REQUEST_METHOD' => 'POST'], user: $this->administrator())->duplicate(new RouteParams(['page' => 'about']));

        $this->assertInstanceOf(RedirectResponse::class, $response, $response->content());
        $this->assertSame('success', $this->lastNotificationType());

        $directory = glob($this->pagesPath . '/*about-copy', GLOB_ONLYDIR)[0] ?? null;

        $this->assertNotNull($directory);
        $this->assertStringContainsString('About copy', FileSystem::read(glob($directory . '/*.md')[0] ?? ''));
        $this->assertDirectoryExists($this->site->findPage('about')?->contentPath() ?? '');
    }

    public function testPagesCanBeDeleted(): void
    {
        $path = $this->site->findPage('about')?->contentPath() ?? '';

        $response = $this->controller()->delete(new RouteParams(['page' => 'about']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('success', $this->lastNotificationType());
        $this->assertDirectoryDoesNotExist($path);
        $this->assertDirectoryExists($this->pagesPath . '/blog');
    }

    public function testPagesCanBeDeletedWithAScriptRequest(): void
    {
        $path = $this->site->findPage('about')?->contentPath() ?? '';

        $response = $this->controller(server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'])->delete(new RouteParams(['page' => 'about']));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertDirectoryDoesNotExist($path);
    }

    public function testPagesThatCannotBeDeletedAreKept(): void
    {
        $path = $this->site->findPage('error')?->contentPath() ?? '';

        $response = $this->controller(server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'])->delete(new RouteParams(['page' => 'error']));

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
        $this->assertDirectoryExists($path);
    }

    public function testPagesAreReordered(): void
    {
        $this->numberPages();

        $response = $this->controller(input: ['page' => '/blog/', 'before' => '/about/', 'parent' => '.'])->reorder();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertDirectoryExists($this->pagesPath . '/1-blog');
        $this->assertDirectoryExists($this->pagesPath . '/2-about');
    }

    public function testReorderingKeepsThePagesThatAreNotOrderable(): void
    {
        $this->numberPages();

        $this->controller(input: ['page' => '/blog/', 'before' => '/about/', 'parent' => '.'])->reorder();

        $this->assertDirectoryExists($this->pagesPath . '/error');
        $this->assertDirectoryExists($this->pagesPath . '/index');
    }

    public function testReorderingPagesRequiresThePermission(): void
    {
        $response = $this->controller(['panel.pages.reorder' => false])->reorder();

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testReorderingRequiresAllTheParameters(): void
    {
        foreach ([[], ['page' => 'about'], ['page' => 'about', 'before' => 'blog'], ['parent' => '.']] as $input) {
            $response = $this->controller(input: $input)->reorder();

            $this->assertInstanceOf(JsonResponse::class, $response);
            $this->assertSame(ResponseStatus::BadRequest, $response->status());
        }
    }

    public function testReorderingAnUnknownPageFails(): void
    {
        $response = $this->controller(input: ['page' => '/missing/', 'before' => '/about/', 'parent' => '.'])->reorder();

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
        $this->assertSame('error', Json::parse($response->content())['status']);
    }

    public function testReorderingBeforeAnUnknownPageFails(): void
    {
        $response = $this->controller(input: ['page' => '/about/', 'before' => '/missing/', 'parent' => '.'])->reorder();

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
    }

    public function testReorderingInAnUnknownParentFails(): void
    {
        $response = $this->controller(input: ['page' => '/about/', 'before' => '/blog/', 'parent' => 'missing'])->reorder();

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
    }

    public function testReorderingInAPageWithoutChildrenFails(): void
    {
        $response = $this->controller(input: ['page' => '/about/', 'before' => '/blog/', 'parent' => 'about'])->reorder();

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
    }

    public function testDeletingPagesRequiresThePermission(): void
    {
        $response = $this->controller(['panel.pages.delete' => false])->delete(new RouteParams(['page' => 'about']));

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
        $this->assertDirectoryExists($this->site->findPage('about')?->contentPath() ?? '');
    }

    public function testUnknownPagesCannotBeDeleted(): void
    {
        $response = $this->controller()->delete(new RouteParams(['page' => 'missing']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
    }

    public function testUnknownPagesCannotBeDeletedWithAScriptRequest(): void
    {
        $response = $this->controller(server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'])->delete(new RouteParams(['page' => 'missing']));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
    }

    public function testPagesCannotBeDeletedInAnUnavailableLanguage(): void
    {
        $response = $this->controller(server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'])->delete(new RouteParams(['page' => 'about', 'language' => 'xx']));

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
        $this->assertDirectoryExists($this->site->findPage('about')?->contentPath() ?? '');
    }

    public function testUploadingFilesRequiresThePermission(): void
    {
        $response = $this->controller(['panel.files.upload' => false])->upload(new RouteParams(['page' => 'about']));

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testUploadingFilesToAnUnknownPageFails(): void
    {
        $response = $this->controller()->upload(new RouteParams(['page' => 'missing']));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
    }

    public function testFilesCanBeUploadedToPages(): void
    {
        $response = $this->controller(server: ['REQUEST_METHOD' => 'POST'], files: ['files' => [$this->uploadedImage()]])->upload(new RouteParams(['page' => 'about']));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::OK, $response->status(), $response->content());
        $this->assertFileExists($this->pagesPath . '/about/photo.png');

        $data = array_values(Json::parse($response->content())['data']);

        $this->assertCount(1, $data);
        $this->assertSame('photo.png', $data[0]['name']);
        $this->assertSame('image/png', $data[0]['mimeType']);
        $this->assertSame('image', $data[0]['type']);
        $this->assertSame(['info', 'rename', 'replace', 'delete'], array_keys($data[0]['actions']));
    }

    public function testSeveralFilesCanBeUploadedToPages(): void
    {
        $response = $this->controller(server: ['REQUEST_METHOD' => 'POST'], files: ['files' => [$this->uploadedImage(clientName: 'first.png'), $this->uploadedImage(clientName: 'second.png')]])->upload(new RouteParams(['page' => 'about']));

        $this->assertSame(ResponseStatus::OK, $response->status(), $response->content());
        $this->assertFileExists($this->pagesPath . '/about/first.png');
        $this->assertFileExists($this->pagesPath . '/about/second.png');
        $this->assertCount(2, Json::parse($response->content())['data']);
    }

    public function testUploadingFilesTouchesTheContentFileOfThePage(): void
    {
        $content = $this->site->findPage('about')?->contentFile()?->path() ?? '';
        touch($content, 1000);

        $this->controller(server: ['REQUEST_METHOD' => 'POST'], files: ['files' => [$this->uploadedImage()]])->upload(new RouteParams(['page' => 'about']));

        clearstatcache();
        $this->assertGreaterThan(1000, filemtime($content));
    }

    public function testFilesOfADisallowedTypeAreNotUploadedToPages(): void
    {
        $response = $this->controller(server: ['REQUEST_METHOD' => 'POST'], files: ['files' => [$this->uploadedText()]])->upload(new RouteParams(['page' => 'about']));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertNotSame(ResponseStatus::OK, $response->status());
        $this->assertFileDoesNotExist($this->pagesPath . '/about/notes.txt');
    }

    public function testUploadingAnExistingFileToAPageIsReportedAsAnError(): void
    {
        $this->controller(server: ['REQUEST_METHOD' => 'POST'], files: ['files' => [$this->uploadedImage()]])->upload(new RouteParams(['page' => 'about']));

        $response = $this->controller(server: ['REQUEST_METHOD' => 'POST'], files: ['files' => [$this->uploadedImage()]])->upload(new RouteParams(['page' => 'about']));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
        $this->assertSame('error', Json::parse($response->content())['status']);
    }

    public function testUploadingWithoutFilesFails(): void
    {
        $response = $this->controller(server: ['REQUEST_METHOD' => 'POST'])->upload(new RouteParams(['page' => 'about']));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
    }

    /**
     * Get the input of the editor of a page, as submitted by the browser
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function pageInput(string $route, array $overrides = []): array
    {
        $page = $this->site->findPage($route);

        $this->assertNotNull($page);

        $input = ['slug' => $page->slug(), 'parent' => $page->parent() instanceof Site ? '.' : (string) $page->parent()?->route(), 'template' => $page->template()->name()];

        foreach ($page->fields() as $name => $field) {
            // The parent and the template are the ones of the page, files are not submitted by the editor
            if (in_array($name, ['parent', 'template', 'files', 'slug'], true)) {
                continue;
            }

            $value = $field->value();

            if ($value === null) {
                continue;
            }

            $input[$name] = match (true) {
                is_bool($value)   => $value ? '1' : '0',
                is_scalar($value) => (string) $value,
                default           => $value,
            };
        }

        return [...$input, ...$overrides];
    }

    /**
     * @return list<string>
     */
    private function pageDirectories(): array
    {
        $directories = [];

        foreach (FileSystem::listRecursive(App::instance()->config()->getString('system.pages.path'), FileSystem::LIST_DIRECTORIES) as $directory) {
            $directories[] = $directory;
        }

        sort($directories);

        return $directories;
    }

    /**
     * Number some pages, so that they can be reordered
     */
    private function numberPages(): void
    {
        rename($this->pagesPath . '/about', $this->pagesPath . '/1-about');
        rename($this->pagesPath . '/blog', $this->pagesPath . '/2-blog');

        $this->site = App::instance()->getService(Container::class)->build(Site::class, ['data' => [
            ...App::instance()->config()->getArray('site'),
            'contentPath' => $this->pagesPath,
        ]]);
        $this->site->load();
    }

    /**
     * @return array<string, string>
     */
    private function newPageInput(): array
    {
        return ['title' => 'Fresh', 'slug' => 'fresh', 'parent' => '.', 'template' => 'default'];
    }

    private function pageFactory(): PageFactory
    {
        return App::instance()->getService(PageFactory::class);
    }

    private function lastNotificationType(): ?string
    {
        $messages = $this->panelRequest?->session()->messages()->getAll() ?? [];

        return array_key_last($messages);
    }

    /**
     * @param array<string, bool>   $permissions
     * @param array<string, mixed>  $input
     * @param array<string, string> $server
     * @param array<string, string> $query
     * @param array<string, object> $services
     * @param array<string, mixed>  $files
     */
    private function controller(array $permissions = ['panel.pages' => true, 'panel.files.upload' => true], array $input = [], array $server = [], array $query = [], array $services = [], ?User $user = null, array $files = []): PagesController
    {
        $fieldFactory = App::instance()->getService(FieldFactory::class);

        $modals = $this->createStub(Modals::class);
        $modals->method('get')->willReturnCallback(function (string $name) use ($fieldFactory): Modal {
            $fields = ['title' => 'text', 'slug' => 'text'];

            if ($name === 'newPage') {
                $fields += ['parent' => 'text', 'template' => 'text'];
            }

            $modal = $this->createStub(Modal::class);
            $modal->method('fields')->willReturnCallback(fn(): FieldCollection => new FieldCollection($this->modalFields($fieldFactory, $name, $fields)));

            return $modal;
        });

        return $this->makeController(
            PagesController::class,
            $permissions,
            input: $input,
            services: [Site::class => $this->site, Modals::class => $modals, FileUploader::class => $this->uploader([$this->pagesPath], $this->pagesPath), ...$services],
            user: $user,
            files: $files,
            query: $query,
            server: $server,
        );
    }

    /**
     * @param array<string, string> $fields Field types by name
     *
     * @return array<string, Field>
     */
    private function modalFields(FieldFactory $fieldFactory, string $modal, array $fields): array
    {
        $result = [];

        foreach ($fields as $name => $type) {
            $result[$name] = $fieldFactory->make("{$modal}Modal.{$name}", ['type' => $type, 'required' => true]);
        }

        return $result;
    }

    /**
     * Create an administrator, whose name is stored in the history of the pages
     */
    private function administrator(): User
    {
        return App::instance()->getService(UserFactory::class)->make([
            'username'     => 'tester',
            'fullname'     => 'Tester',
            'email'        => 'tester@example.test',
            'role'         => 'admin',
            'language'     => 'en',
            'passwordHash' => password_hash('password', PASSWORD_DEFAULT),
        ]);
    }
}
