<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Fields\Field;
use Formwork\Fields\FieldCollection;
use Formwork\Fields\FieldFactory;
use Formwork\Files\Services\FileUploader;
use Formwork\Pages\Page;
use Formwork\Panel\ContentHistory\ContentHistory;
use Formwork\Panel\ContentHistory\ContentHistoryEvent;
use Formwork\Panel\ContentHistory\ContentHistoryItem;
use Formwork\Panel\Controllers\PagesController;
use Formwork\Panel\Modals\Modal;
use Formwork\Panel\Modals\Modals;
use Formwork\Schemes\Schemes;
use Formwork\Services\Container;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\BuildsPanelControllers;
use Formwork\Tests\Unit\Panel\BuildsUploads;
use Formwork\Tests\Unit\Users\BuildsPasswordHashes;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Users\User;
use Formwork\Users\UserFactory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(PagesController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class PagesControllerActionsTest extends TestCase
{
    use BuildsPanelControllers;
    use BuildsUploads;
    use BuildsPasswordHashes;

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

    #[DataProvider('deniedActionProvider')]
    public function testActionsRequireTheirPermission(string $action, string $permission): void
    {
        $this->assertOutcome(['status' => 403], $this->call($this->controller([$permission => false]), $action, ['page' => 'about']));
        $this->assertDirectoryExists($this->pagesPath . '/about');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function deniedActionProvider(): iterable
    {
        yield 'index' => ['index', 'panel.pages.tree'];
        yield 'tree' => ['tree', 'panel.pages.tree'];

        foreach (['create', 'duplicate', 'edit', 'preview', 'delete', 'reorder'] as $action) {
            yield $action => [$action, "panel.pages.{$action}"];
        }

        yield 'upload' => ['upload', 'panel.files.upload'];
    }

    /**
     * @param array<string, string> $params
     * @param array<string, mixed>  $setup    Request `input`, `server`, `query` and kinds of `uploads`
     * @param array<string, mixed>  $expected
     * @param array<string, bool>   $dirs     Whether the directories (relative to the pages) must exist after the action
     */
    #[DataProvider('actionOutcomeProvider')]
    public function testActionsRespondAsExpected(string $action, array $params, array $setup, array $expected, array $dirs = []): void
    {
        $before = $this->pageDirectories();

        $controller = $this->controller(
            input: $setup['input'] ?? [],
            server: $setup['server'] ?? [],
            query: $setup['query'] ?? [],
            files: $setup['uploads'] ?? [] ? ['files' => $this->uploadedFiles($setup['uploads'])] : [],
        );

        $this->assertOutcome($expected, $this->call($controller, $action, $params));

        foreach ($dirs as $dir => $exists) {
            $exists ? $this->assertDirectoryExists($this->pagesPath . '/' . $dir) : $this->assertDirectoryDoesNotExist($this->pagesPath . '/' . $dir);
        }

        if ($dirs === [] && ($expected['notices'] ?? null) === ['error']) {
            $this->assertSame($before, $this->pageDirectories());
        }
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function actionOutcomeProvider(): iterable
    {
        $post = ['REQUEST_METHOD' => 'POST'];
        $ajax = ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];
        $reorder = static fn(array $input): array => ['input' => $input];
        $error = ['notices' => ['error']];

        yield 'index redirects to the tree' => ['index', [], [], ['to' => 'panel.pages.tree']];
        yield 'tree of the site' => ['tree', [], [], ['status' => 200]];
        yield 'tree of an unknown page' => ['tree', ['page' => 'missing'], [], ['status' => 404]];
        yield 'tree of a page without children' => ['tree', ['page' => 'about'], [], ['status' => 404]];
        yield 'creation of an invalid page' => ['create', [], ['input' => ['newPageModal' => ['title' => '']], 'server' => $post], $error];
        yield 'duplication of an unknown page' => ['duplicate', ['page' => 'missing'], [], $error];
        yield 'duplication with an invalid title' => ['duplicate', ['page' => 'about'], ['input' => ['duplicatePageModal' => ['title' => '']], 'server' => $post], $error];
        yield 'edit of an unknown page' => ['edit', ['page' => 'missing'], [], $error];
        yield 'preview of an unknown page' => ['preview', ['page' => 'missing'], [], $error];
        yield 'delete' => ['delete', ['page' => 'about'], [], ['notices' => ['success']], ['about' => false, 'blog' => true]];
        yield 'delete with a script request' => ['delete', ['page' => 'about'], ['server' => $ajax], ['status' => 200], ['about' => false]];
        yield 'delete of a page that cannot be deleted' => ['delete', ['page' => 'error'], ['server' => $ajax], ['status' => 500], ['error' => true]];
        yield 'delete of an unknown page' => ['delete', ['page' => 'missing'], [], $error];
        yield 'delete of an unknown page with a script request' => ['delete', ['page' => 'missing'], ['server' => $ajax], ['status' => 500]];
        yield 'delete in an unavailable language' => ['delete', ['page' => 'about', 'language' => 'xx'], ['server' => $ajax], ['status' => 500], ['about' => true]];
        yield 'reorder without parameters' => ['reorder', [], $reorder([]), ['status' => 400]];
        yield 'reorder without the page before' => ['reorder', [], $reorder(['page' => 'about']), ['status' => 400]];
        yield 'reorder without the parent' => ['reorder', [], $reorder(['page' => 'about', 'before' => 'blog']), ['status' => 400]];
        yield 'reorder of an unknown page' => ['reorder', [], $reorder(['page' => '/missing/', 'before' => '/about/', 'parent' => '.']), ['status' => 500, 'json' => ['status' => 'error']]];
        yield 'reorder before an unknown page' => ['reorder', [], $reorder(['page' => '/about/', 'before' => '/missing/', 'parent' => '.']), ['status' => 500]];
        yield 'reorder in a page without children' => ['reorder', [], $reorder(['page' => '/about/', 'before' => '/blog/', 'parent' => 'about']), ['status' => 500]];
        yield 'upload to an unknown page' => ['upload', ['page' => 'missing'], [], ['status' => 500]];
        yield 'upload without files' => ['upload', ['page' => 'about'], ['server' => $post], ['status' => 500]];
        yield 'upload of an empty file field' => ['upload', ['page' => 'about'], ['server' => $post, 'uploads' => [UPLOAD_ERR_NO_FILE]], ['status' => 500]];

        foreach (self::uploadErrorProvider() as $name => [$error]) {
            yield "upload of a file failing with a PHP error ($name)" => ['upload', ['page' => 'about'], ['server' => $post, 'uploads' => [$error]], ['status' => 500, 'json' => ['status' => 'error']], ['about' => true]];
        }
    }

    /**
     * @param array<string, mixed> $setup
     */
    #[DataProvider('failingActionProvider')]
    public function testFailingActionsReturnAnErrorResponse(string $action, array $setup): void
    {
        $controller = fn(): PagesController => $this->controller(input: $setup['input'] ?? [], server: ['REQUEST_METHOD' => 'POST'], files: isset($setup['uploads']) ? ['files' => $this->uploadedFiles($setup['uploads'])] : []);

        if ($setup['twice'] ?? false) {
            $this->call($controller(), $action, ['page' => 'about']);
        }

        $this->assertOutcome(['status' => 500], $this->call($controller(), $action, ['page' => 'about']));
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function failingActionProvider(): iterable
    {
        yield 'reorder in an unknown parent' => ['reorder', ['input' => ['page' => '/about/', 'before' => '/blog/', 'parent' => 'missing']]];
        yield 'upload of a file of a disallowed type' => ['upload', ['uploads' => ['text']]];
        yield 'upload of a file with an existing name' => ['upload', ['uploads' => ['png'], 'twice' => true]];
    }

    public function testEditorOpensTheNewPageModalOnRequest(): void
    {
        $modal = $this->createMock(Modal::class);
        $modal->expects($this->once())->method('open')->with(true);

        $modals = $this->createStub(Modals::class);
        $modals->method('get')->willReturn($modal);

        $this->call($this->controller(server: ['REQUEST_METHOD' => 'GET'], query: ['createNew' => ''], services: [Modals::class => $modals]), 'edit', ['page' => 'about']);
    }

    public function testInvalidPagesAreNotSaved(): void
    {
        $content = (string) $this->site->findPage('about')?->contentFile()?->path();
        $original = FileSystem::read($content);

        $response = $this->call($this->controller(input: ['title' => ''], server: ['REQUEST_METHOD' => 'POST']), 'edit', ['page' => 'about']);

        $this->assertOutcome(['status' => 422, 'notices' => ['error']], $response);
        $this->assertSame($original, FileSystem::read($content));
    }

    public function testPagesCanBePreviewedWithoutChangingThem(): void
    {
        $page = $this->site->findPage('about');
        $published = $page?->get('published');

        $response = $this->call($this->controller(input: $this->pageInput('about', ['title' => 'Preview title']), server: ['REQUEST_METHOD' => 'POST']), 'preview', ['page' => 'about']);

        $this->assertOutcome(['status' => 200], $response);
        $this->assertStringContainsString('About', $response->content());
        $this->assertSame($published, $page?->get('published'));
    }

    public function testPagesAreEditedAndTheEditIsRecordedInTheHistory(): void
    {
        $page = $this->site->findPage('about');
        $input = $this->pageInput('about', ['title' => 'About us', 'content' => 'New content']);

        $response = $this->call($this->controller(input: $input, server: ['REQUEST_METHOD' => 'POST'], user: $this->administrator()), 'edit', ['page' => 'about']);

        $this->assertOutcome(['to' => 'panel.pages.edit', 'notices' => ['success']], $response);
        $this->assertStringContainsString('About us', FileSystem::read((string) $page?->contentFile()?->path()));

        $item = (new ContentHistory((string) $page?->contentPath()))->lastItem();

        $this->assertInstanceOf(ContentHistoryItem::class, $item);
        $this->assertSame('tester', $item->user());
        $this->assertSame(ContentHistoryEvent::Edited, $item->event());
    }

    public function testUnchangedPagesAreNotAddedToTheHistory(): void
    {
        $this->call($this->controller(input: $this->pageInput('about'), server: ['REQUEST_METHOD' => 'POST'], user: $this->administrator()), 'edit', ['page' => 'about']);

        $this->assertFileDoesNotExist(($this->site->findPage('about')?->contentPath() ?? '') . '/.history');
    }

    public function testPagesCanBePublishedWhileEditing(): void
    {
        $page = $this->site->findPage('about');

        $this->call($this->controller(input: $this->pageInput('about', ['published' => '0']), server: ['REQUEST_METHOD' => 'POST'], query: ['publish' => '1'], user: $this->administrator()), 'edit', ['page' => 'about']);

        $this->assertTrue($page?->get('published'));
    }

    public function testNewPagesAreCreatedUnpublishedAndRecordedInTheHistory(): void
    {
        $response = $this->call($this->controller(input: ['newPageModal' => ['title' => 'Fresh', 'slug' => 'fresh', 'parent' => '.', 'template' => 'default']], server: ['REQUEST_METHOD' => 'POST'], user: $this->administrator()), 'create');

        $this->assertOutcome(['to' => 'panel.pages.edit', 'notices' => ['success']], $response);

        $directory = glob($this->pagesPath . '/*fresh', GLOB_ONLYDIR)[0] ?? '';

        $this->assertStringContainsString('published: false', FileSystem::read(glob($directory . '/*.md')[0] ?? ''));
        $this->assertTrue((new ContentHistory($directory))->isJustCreated());
    }

    public function testPagesCanBeDuplicated(): void
    {
        $response = $this->call($this->controller(input: ['duplicatePageModal' => ['title' => 'About copy', 'slug' => 'about-copy']], server: ['REQUEST_METHOD' => 'POST'], user: $this->administrator()), 'duplicate', ['page' => 'about']);

        $this->assertOutcome(['notices' => ['success']], $response);

        $directory = glob($this->pagesPath . '/*about-copy', GLOB_ONLYDIR)[0] ?? '';

        $this->assertStringContainsString('About copy', FileSystem::read(glob($directory . '/*.md')[0] ?? ''));
        $this->assertDirectoryExists($this->pagesPath . '/about');
    }

    public function testPagesAreReordered(): void
    {
        $this->numberPages();

        $response = $this->call($this->controller(input: ['page' => '/blog/', 'before' => '/about/', 'parent' => '.']), 'reorder');

        $this->assertOutcome(['status' => 200], $response);
        $this->assertDirectoryExists($this->pagesPath . '/1-blog');
        $this->assertDirectoryExists($this->pagesPath . '/2-about');
        // Pages that are not orderable are left alone
        $this->assertDirectoryExists($this->pagesPath . '/error');
        $this->assertDirectoryExists($this->pagesPath . '/index');
    }

    public function testFilesAreUploadedToPages(): void
    {
        $content = (string) $this->site->findPage('about')?->contentFile()?->path();
        touch($content, 1000);

        $uploads = $this->uploadedFiles(['png', 'png2']);
        $response = $this->call($this->controller(server: ['REQUEST_METHOD' => 'POST'], files: ['files' => $uploads]), 'upload', ['page' => 'about']);

        $this->assertOutcome(['status' => 200], $response);
        $this->assertFileExists($this->pagesPath . '/about/photo.png');
        $this->assertFileExists($this->pagesPath . '/about/second.png');

        $data = array_values($this->outcome($response)['json']['data']);

        $this->assertSame(['photo.png', 'second.png'], array_column($data, 'name'));
        $this->assertSame(['image/png', 'image/png'], array_column($data, 'mimeType'));
        $this->assertSame(['info', 'rename', 'replace', 'delete'], array_keys($data[0]['actions']));

        clearstatcache();
        $this->assertGreaterThan(1000, filemtime($content));
    }

    public function testFailedUploadsDoNotPreventTheOtherOnes(): void
    {
        $uploads = $this->uploadedFiles([UPLOAD_ERR_INI_SIZE, 'png2']);
        $response = $this->call($this->controller(server: ['REQUEST_METHOD' => 'POST'], files: ['files' => $uploads]), 'upload', ['page' => 'about']);

        $this->assertOutcome(['status' => 200], $response);
        $this->assertFileExists($this->pagesPath . '/about/second.png');
        $this->assertFileDoesNotExist($this->pagesPath . '/about/big.png');
        $this->assertCount(1, $this->outcome($response)['json']['data']);
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

        $this->assertInstanceOf(Page::class, $page);

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
            query: $query,
            server: $server,
            files: $files,
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
            'passwordHash' => self::hashPassword('password'),
        ]);
    }
}
