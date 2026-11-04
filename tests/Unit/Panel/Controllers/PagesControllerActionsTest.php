<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Fields\FieldCollection;
use Formwork\Fields\FieldFactory;
use Formwork\Http\JsonResponse;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Pages\PageFactory;
use Formwork\Panel\Controllers\PagesController;
use Formwork\Panel\Modals\Modal;
use Formwork\Panel\Modals\Modals;
use Formwork\Parsers\Json;
use Formwork\Router\RouteParams;
use Formwork\Schemes\Schemes;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\Fixtures\BuildsPanelControllers;
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

    protected function setUp(): void
    {
        parent::setUp();

        // `FieldFactory` is registered lazily by the schemes service loader
        App::instance()->getService(Schemes::class);
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
        $content = App::instance()->site()->findPage('about')?->contentFile()?->path();

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
        $page = App::instance()->site()->findPage('about');

        $this->assertNotNull($page);

        $published = $page->get('published');
        $cacheable = $page->get('cacheable');

        $this->controller(input: $this->pageInput('about'), server: ['REQUEST_METHOD' => 'POST'])->preview(new RouteParams(['page' => 'about']));

        $this->assertSame($published, $page->get('published'));
        $this->assertSame($cacheable, $page->get('cacheable'));
    }

    public function testPagesCanBeEdited(): void
    {
        $page = App::instance()->site()->findPage('about');

        $this->assertNotNull($page);
        $this->assertNotNull($page->contentPath());

        $content = $page->contentFile()?->path();
        $history = $page->contentPath() . '/.history';
        $original = FileSystem::read((string) $content);
        $data = ['title' => $page->get('title'), 'content' => $page->get('content')];

        try {
            $response = $this->controller(input: $this->pageInput('about', ['title' => 'About us', 'content' => 'New content']), server: ['REQUEST_METHOD' => 'POST'], user: $this->administrator())->edit(new RouteParams(['page' => 'about']));

            $this->assertInstanceOf(RedirectResponse::class, $response, $response->content());
            $this->assertStringEndsWith('/route/panel.pages.edit', $response->headers()->get('Location'));
            $this->assertStringContainsString('About us', FileSystem::read((string) $content));
            $this->assertFileExists($history);
        } finally {
            // The page is shared with the other tests, restore also the loaded data
            $page->setMultiple($data);
            FileSystem::write((string) $content, $original);

            if (FileSystem::exists($history)) {
                FileSystem::delete($history);
            }
        }
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
        $response = $this->controller(input: ['page' => 'missing', 'before' => 'about', 'parent' => '.'])->reorder();

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
        $this->assertSame('error', Json::parse($response->content())['status']);
    }

    public function testReorderingBeforeAnUnknownPageFails(): void
    {
        $response = $this->controller(input: ['page' => 'about', 'before' => 'missing', 'parent' => '.'])->reorder();

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
    }

    public function testReorderingInAnUnknownParentFails(): void
    {
        $response = $this->controller(input: ['page' => 'about', 'before' => 'blog', 'parent' => 'missing'])->reorder();

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
    }

    public function testReorderingInAPageWithoutChildrenFails(): void
    {
        $response = $this->controller(input: ['page' => 'about', 'before' => 'blog', 'parent' => 'about'])->reorder();

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
    }

    public function testDeletingPagesRequiresThePermission(): void
    {
        $response = $this->controller(['panel.pages.delete' => false])->delete(new RouteParams(['page' => 'about']));

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
        $this->assertDirectoryExists(App::instance()->site()->findPage('about')?->contentPath() ?? '');
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
        $this->assertDirectoryExists(App::instance()->site()->findPage('about')?->contentPath() ?? '');
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
        $page = App::instance()->site()->findPage($route);

        $this->assertNotNull($page);

        $input = ['slug' => $page->slug()];

        foreach ($page->fields() as $name => $field) {
            $value = $field->value();
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
     */
    private function controller(array $permissions = ['panel.pages' => true, 'panel.files.upload' => true], array $input = [], array $server = [], array $query = [], array $services = [], ?User $user = null): PagesController
    {
        $fieldFactory = App::instance()->getService(FieldFactory::class);

        $modal = $this->createStub(Modal::class);
        $modal->method('fields')->willReturn(new FieldCollection([
            'title' => $fieldFactory->make('newPageModal.title', ['type' => 'text', 'required' => true]),
        ]));

        $modals = $this->createStub(Modals::class);
        $modals->method('get')->willReturn($modal);

        return $this->makeController(
            PagesController::class,
            $permissions,
            input: $input,
            services: [Site::class => App::instance()->site(), Modals::class => $modals, ...$services],
            user: $user,
            query: $query,
            server: $server,
        );
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
