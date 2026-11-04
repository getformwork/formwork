<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Fields\FieldCollection;
use Formwork\Fields\FieldFactory;
use Formwork\Files\FileCollection;
use Formwork\Files\FileFactory;
use Formwork\Files\Services\FileUploader;
use Formwork\Http\Files\UploadedFile;
use Formwork\Http\JsonResponse;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Pages\ContentFile;
use Formwork\Pages\Page;
use Formwork\Pages\PageCollection;
use Formwork\Pages\PaginationFactory;
use Formwork\Panel\Controllers\FilesController;
use Formwork\Panel\Modals\Modal;
use Formwork\Panel\Modals\Modals;
use Formwork\Parsers\Json;
use Formwork\Router\RouteParams;
use Formwork\Router\Router;
use Formwork\Schemes\Schemes;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\Fixtures\BuildsPanelControllers;
use Formwork\Tests\Unit\Panel\Fixtures\CreatesUploads;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(FilesController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class FilesControllerActionsTest extends TestCase
{
    use BuildsPanelControllers;
    use CreatesUploads;

    private string $contentPath;

    private Page $page;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        // `FieldFactory` is registered lazily by the schemes service loader
        App::instance()->getService(Schemes::class);

        // Files in the pages path get an URI
        $this->contentPath = App::instance()->config()->getString('system.pages.path') . '/files-controller-test';
        FileSystem::createDirectory($this->contentPath);
        FileSystem::write($this->contentPath . '/b.txt', 'bee');
        FileSystem::write($this->contentPath . '/a.txt', 'a');
        FileSystem::write($this->contentPath . '/c.txt', 'cee');
        FileSystem::write($this->contentPath . '/a.txt.meta.yaml', "title: Letter A\n");
        FileSystem::write($this->contentPath . '/page.md', 'content');
        touch($this->contentPath . '/page.md', 1000);

        $this->page = $this->createStub(Page::class);
        $this->page->method('route')->willReturn('/about/');
        $this->page->method('getModelIdentifier')->willReturn('page');
        $this->page->method('contentPath')->willReturn($this->contentPath);
        $this->page->method('files')->willReturnCallback(fn(): FileCollection => $this->files());
        $this->page->method('contentFile')->willReturn(null);

        $descendants = new PageCollection(['/about/' => $this->page], new PaginationFactory(App::instance(), $this->createStub(Router::class)));

        $this->site = $this->createStub(Site::class);
        $this->site->method('findPage')->willReturnCallback(fn(string $route): ?Page => $route === '/about/' ? $this->page : null);
        $this->site->method('files')->willReturn(new FileCollection([]));
        $this->site->method('descendants')->willReturn($descendants);
        $this->site->method('route')->willReturn('/');
        $this->site->method('getModelIdentifier')->willReturn('site');
    }

    protected function tearDown(): void
    {
        FileSystem::deleteDirectory($this->contentPath, recursive: true);

        $this->closePanelSession();
        parent::tearDown();
    }

    public function testIndexRequiresThePermission(): void
    {
        $response = $this->controller(['panel.files.index' => false])->index();

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testIndexListsTheFilesOfThePagesSortedByName(): void
    {
        $view = $this->renderedView($this->controller()->index());

        $this->assertSame('@panel.files.index', $view['view']);
        $this->assertSame(['a.txt', 'b.txt', 'c.txt'], array_map(fn(array $item): string => $item[0]['name'], $view['vars']['files']));
    }

    public function testListRequiresThePermission(): void
    {
        $response = $this->controller(['panel.files.list' => false])->list(new RouteParams(['model' => 'page', 'id' => '/about/']));

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testListOfAnUnknownPageIsNotFound(): void
    {
        $response = $this->controller()->list(new RouteParams(['model' => 'page', 'id' => '/missing/']));

        $this->assertSame(ResponseStatus::NotFound, $response->status());
    }

    public function testListReturnsTheFilesOfTheModel(): void
    {
        $response = $this->controller()->list(new RouteParams(['model' => 'page', 'id' => '/about/']));

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = Json::parse($response->content())['data'];

        $this->assertSame(['a.txt', 'b.txt', 'c.txt'], array_column($data, 'filename'));
        $this->assertSame(['1 B', '3 B', '3 B'], array_column($data, 'size'));
        $this->assertSame(['info', 'rename', 'replace', 'delete'], array_keys($data[0]['actions']));
        $this->assertNull($data[0]['thumbnail']);
    }

    public function testEditRequiresThePermission(): void
    {
        $response = $this->controller(['panel.files.edit' => false])->edit($this->params('b.txt'));

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testEditOfAnUnknownFileRedirectsWithAnError(): void
    {
        $response = $this->controller()->edit($this->params('missing.txt'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
    }

    public function testEditDisplaysTheFileWithItsNeighbours(): void
    {
        $response = $this->controller(server: ['REQUEST_METHOD' => 'GET'])->edit($this->params('b.txt'));
        $view = $this->renderedView($response);

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertSame('@panel.files.edit', $view['view']);
        $this->assertSame('b.txt', $view['vars']['title']);
        $this->assertSame('a.txt', $view['vars']['previousFile']['name']);
        $this->assertSame('c.txt', $view['vars']['nextFile']['name']);
    }

    public function testTheFirstFileHasNoPreviousFile(): void
    {
        $view = $this->renderedView($this->controller(server: ['REQUEST_METHOD' => 'GET'])->edit($this->params('a.txt')));

        $this->assertNull($view['vars']['previousFile']);
        $this->assertSame('b.txt', $view['vars']['nextFile']['name']);
    }

    public function testTheLastFileHasNoNextFile(): void
    {
        $view = $this->renderedView($this->controller(server: ['REQUEST_METHOD' => 'GET'])->edit($this->params('c.txt')));

        $this->assertSame('b.txt', $view['vars']['previousFile']['name']);
        $this->assertNull($view['vars']['nextFile']);
    }

    public function testDeleteRequiresThePermission(): void
    {
        $response = $this->controller(['panel.files.delete' => false])->delete($this->params('b.txt'));

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
        $this->assertFileExists($this->contentPath . '/b.txt');
    }

    public function testFilesCanBeDeleted(): void
    {
        $response = $this->controller()->delete($this->params('b.txt'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('success', $this->lastNotificationType());
        $this->assertFileDoesNotExist($this->contentPath . '/b.txt');
        $this->assertFileExists($this->contentPath . '/a.txt');
    }

    public function testFilesCanBeDeletedWithAScriptRequest(): void
    {
        $response = $this->controller(server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'])->delete($this->params('b.txt'));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertFileDoesNotExist($this->contentPath . '/b.txt');
    }

    public function testUnknownFilesCannotBeDeleted(): void
    {
        $response = $this->controller()->delete($this->params('missing.txt'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
    }

    public function testUnknownFilesCannotBeDeletedWithAScriptRequest(): void
    {
        $response = $this->controller(server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'])->delete($this->params('missing.txt'));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
    }

    public function testDeletingAFileDoesNotLeaveItsMetadataBehind(): void
    {
        $this->controller()->delete($this->params('a.txt'));

        $this->assertFileDoesNotExist($this->contentPath . '/a.txt');
        $this->assertFileDoesNotExist($this->contentPath . '/a.txt.meta.yaml');
    }

    public function testDeletingAFileTouchesTheContentFileOfThePage(): void
    {
        $contentFile = $this->createStub(ContentFile::class);
        $contentFile->method('path')->willReturn($this->contentPath . '/page.md');

        $page = $this->createStub(Page::class);
        $page->method('files')->willReturnCallback(fn(): FileCollection => $this->files());
        $page->method('contentFile')->willReturn($contentFile);

        $site = $this->createStub(Site::class);
        $site->method('findPage')->willReturn($page);

        $this->controller(services: [Site::class => $site])->delete($this->params('b.txt'));

        clearstatcache();
        $this->assertGreaterThan(1000, filemtime($this->contentPath . '/page.md'));
    }

    public function testRenamingRequiresThePermission(): void
    {
        $response = $this->controller(['panel.files.rename' => false])->rename($this->params('b.txt'), $this->fileFactory());

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testFilesCanBeRenamed(): void
    {
        $response = $this->rename('b.txt', 'New name');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('success', $this->lastNotificationType());
        $this->assertFileExists($this->contentPath . '/new-name.txt');
        $this->assertFileDoesNotExist($this->contentPath . '/b.txt');
    }

    public function testRenamedFilesKeepTheirExtension(): void
    {
        $this->rename('b.txt', 'Report.pdf');

        $this->assertFileExists($this->contentPath . '/report.txt');
    }

    public function testRenamingToTheSameNameChangesNothing(): void
    {
        $response = $this->rename('b.txt', 'b');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertFileExists($this->contentPath . '/b.txt');
    }

    public function testFilesCannotBeRenamedToAnExistingName(): void
    {
        $response = $this->rename('b.txt', 'c');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
        $this->assertSame('bee', FileSystem::read($this->contentPath . '/b.txt'));
        $this->assertSame('cee', FileSystem::read($this->contentPath . '/c.txt'));
    }

    public function testRenamingKeepsTheMetadataOfTheFile(): void
    {
        $this->rename('a.txt', 'Letter');

        $this->assertFileExists($this->contentPath . '/letter.txt');
        $this->assertFileExists($this->contentPath . '/letter.txt.meta.yaml');
        $this->assertFileDoesNotExist($this->contentPath . '/a.txt.meta.yaml');
    }

    public function testFilesCannotBeRenamedToAnEmptyName(): void
    {
        $this->rename('b.txt', '!!!');

        $this->assertFileExists($this->contentPath . '/b.txt');
        $this->assertFileDoesNotExist($this->contentPath . '/.txt');
    }

    public function testRenamingWithAScriptRequestReturnsTheNewFile(): void
    {
        $response = $this->rename('b.txt', 'New name', ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = Json::parse($response->content())['data'];

        $this->assertSame('new-name.txt', $data['filename']);
        $this->assertSame('3 B', $data['size']);
    }

    public function testRenamingAnUnknownFileWithAScriptRequestFails(): void
    {
        $response = $this->rename('missing.txt', 'New name', ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
    }

    public function testRenamingWithAnInvalidFormFails(): void
    {
        $response = $this->rename('b.txt', '');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
        $this->assertFileExists($this->contentPath . '/b.txt');
    }

    public function testRenamingWithAnInvalidFormAndAScriptRequestIsABadRequest(): void
    {
        $response = $this->rename('b.txt', '', ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        $this->assertSame(ResponseStatus::BadRequest, $response->status());
    }

    public function testReplacingRequiresThePermission(): void
    {
        $response = $this->controller(['panel.files.replace' => false])->replace($this->params('b.txt'), $this->createStub(FileUploader::class));

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testReplacingAnUnknownFileFails(): void
    {
        $response = $this->controller()->replace($this->params('missing.txt'), $this->createStub(FileUploader::class));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
    }

    public function testReplacingAnUnknownFileWithAScriptRequestFails(): void
    {
        $response = $this->controller(server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'])->replace($this->params('missing.txt'), $this->createStub(FileUploader::class));

        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
    }

    public function testReplacingWithoutUploadsOnlyUpdatesTheModifiedTime(): void
    {
        $response = $this->controller()->replace($this->params('b.txt'), $this->createStub(FileUploader::class));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('success', $this->lastNotificationType());
        $this->assertSame('bee', FileSystem::read($this->contentPath . '/b.txt'));
    }

    public function testUploadingFilesRequiresThePermission(): void
    {
        $response = $this->controller(['panel.files.upload' => false])->upload($this->createStub(FileUploader::class));

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testFilesCanBeUploadedToTheSite(): void
    {
        $destination = TESTS_TMP_PATH . '/site-files';

        $response = $this->upload(['parent' => '.'], [$this->uploadedImage('files')], $destination);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('success', $this->lastNotificationType());
        $this->assertFileExists($destination . '/photo.png');
    }

    public function testSeveralFilesCanBeUploaded(): void
    {
        $destination = TESTS_TMP_PATH . '/site-files';

        $this->upload(['parent' => '.'], [$this->uploadedImage('files', 'first.png'), $this->uploadedImage('files', 'second.png')], $destination);

        $this->assertFileExists($destination . '/first.png');
        $this->assertFileExists($destination . '/second.png');
    }

    public function testFilesOfADisallowedTypeAreNotUploaded(): void
    {
        $destination = TESTS_TMP_PATH . '/site-files';

        $response = $this->upload(['parent' => '.'], [$this->uploadedText('files')], $destination);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
        $this->assertFileDoesNotExist($destination . '/notes.txt');
    }

    public function testExistingFilesAreNotOverwrittenByUploads(): void
    {
        $destination = TESTS_TMP_PATH . '/site-files';

        $this->upload(['parent' => '.'], [$this->uploadedImage('files')], $destination);
        $original = FileSystem::read($destination . '/photo.png');

        $response = $this->upload(['parent' => '.'], [$this->uploadedImage('files')], $destination);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
        $this->assertSame($original, FileSystem::read($destination . '/photo.png'));
    }

    #[DataProvider('uploadErrorCodeProvider')]
    public function testFilesFailingWithAPhpErrorAreNotUploaded(int $error): void
    {
        $destination = TESTS_TMP_PATH . '/site-files';

        $response = $this->upload(['parent' => '.'], [$this->failedUpload($error)], $destination);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
        $this->assertFileDoesNotExist($destination . '/big.png');
    }

    public function testUploadingWithoutFilesIsAnInvalidForm(): void
    {
        $destination = TESTS_TMP_PATH . '/site-files';

        $response = $this->upload(['parent' => '.'], [], $destination);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
        $this->assertFileDoesNotExist($destination);
    }

    #[DataProvider('uploadErrorProvider')]
    public function testFilesCannotBeReplacedByAFileFailingWithAPhpError(int $error, string $languageString): void
    {
        $response = $this->replace('b.txt', [$this->failedUpload($error, 'file', 'new.txt')]);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(['error' => [$this->uploadErrorText($languageString)]], $this->notifications());
        $this->assertSame('bee', FileSystem::read($this->contentPath . '/b.txt'));
    }

    #[DataProvider('uploadErrorProvider')]
    public function testFilesCannotBeReplacedByAFileFailingWithAPhpErrorWithAScriptRequest(int $error, string $languageString): void
    {
        $response = $this->replace('b.txt', [$this->failedUpload($error, 'file', 'new.txt')], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::InternalServerError, $response->status());
        $this->assertSame($this->uploadErrorText($languageString), Json::parse($response->content())['message']);
        $this->assertSame('bee', FileSystem::read($this->contentPath . '/b.txt'));
    }

    public function testReplacingRequiresTheFileOfTheSameType(): void
    {
        $response = $this->replace('b.txt', [$this->uploadedImage('file', 'other.png')]);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
        $this->assertSame('bee', FileSystem::read($this->contentPath . '/b.txt'));
    }

    public function testFilesCanBeReplaced(): void
    {
        $response = $this->replace('b.txt', [$this->uploadedText('file', 'new.txt')]);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('success', $this->lastNotificationType());
        $this->assertSame('plain text', FileSystem::read($this->contentPath . '/b.txt'));
        $this->assertFileDoesNotExist($this->contentPath . '/new.txt');
    }

    public function testFilesCanBeReplacedWithAScriptRequest(): void
    {
        $response = $this->replace('b.txt', [$this->uploadedText('file', 'new.txt')], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(ResponseStatus::OK, $response->status(), $response->content());
        $this->assertSame('b.txt', Json::parse($response->content())['data']['filename']);
        $this->assertSame('plain text', FileSystem::read($this->contentPath . '/b.txt'));
    }

    public function testOnlyOneFileCanReplaceAFile(): void
    {
        $response = $this->replace('b.txt', [$this->uploadedText('file', 'one.txt'), $this->uploadedText('file', 'two.txt')]);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
        $this->assertSame('bee', FileSystem::read($this->contentPath . '/b.txt'));
    }

    public function testReplacingKeepsTheMetadataOfTheFile(): void
    {
        $this->replace('a.txt', [$this->uploadedText('file', 'new.txt')]);

        $this->assertFileExists($this->contentPath . '/a.txt.meta.yaml');
        $this->assertSame("title: Letter A\n", FileSystem::read($this->contentPath . '/a.txt.meta.yaml'));
    }

    /**
     * @param list<UploadedFile>    $files
     * @param array<string, string> $server
     */
    private function replace(string $filename, array $files, array $server = []): Response
    {
        return $this->controller(files: ['file' => $files], server: $server, services: [FileUploader::class => $this->uploader([$this->contentPath])])
            ->replace($this->params($filename), $this->uploader([$this->contentPath]));
    }

    /**
     * @param array<string, string> $input
     * @param list<UploadedFile>    $files
     */
    private function upload(array $input, array $files, string $destination): Response
    {
        $fieldFactory = App::instance()->getService(FieldFactory::class);

        $modal = $this->createStub(Modal::class);
        $modal->method('fields')->willReturnCallback(fn(): FieldCollection => new FieldCollection([
            'files'  => $fieldFactory->make('uploadFileModal.files', ['type' => 'upload', 'multiple' => true]),
            'parent' => $fieldFactory->make('uploadFileModal.parent', ['type' => 'page', 'required' => true, 'allowSite' => true]),
        ]));

        $modals = $this->createStub(Modals::class);
        $modals->method('get')->willReturn($modal);

        $uploader = $this->uploader([$destination]);

        if ($files !== [] && !FileSystem::isDirectory($destination, assertExists: false)) {
            FileSystem::createDirectory($destination);
        }

        $controller = $this->makeController(
            FilesController::class,
            ['panel.files' => true],
            ['files' => ['paths' => ['site' => $destination]]],
            ['uploadFileModal' => $input],
            [Site::class => $this->site, Modals::class => $modals, FileUploader::class => $uploader],
            files: $files === [] ? [] : ['uploadFileModal' => ['files' => $files]],
            server: ['REQUEST_METHOD' => 'POST'],
        );

        return $controller->upload($uploader);
    }

    private function files(): FileCollection
    {
        $factory = $this->fileFactory();

        $files = [];

        foreach (['a.txt', 'b.txt', 'c.txt'] as $name) {
            if (FileSystem::exists($this->contentPath . '/' . $name)) {
                $files[$name] = $factory->make($this->contentPath . '/' . $name);
            }
        }

        return new FileCollection($files);
    }

    private function fileFactory(): FileFactory
    {
        return App::instance()->getService(FileFactory::class);
    }

    private function params(string $filename): RouteParams
    {
        return new RouteParams(['model' => 'page', 'id' => '/about/', 'filename' => $filename]);
    }

    /**
     * @return array<string, list<string>>
     */
    private function notifications(): array
    {
        return $this->panelRequest?->session()->messages()->getAll() ?? [];
    }

    private function lastNotificationType(): ?string
    {
        $messages = $this->panelRequest?->session()->messages()->getAll() ?? [];

        return array_key_last($messages);
    }

    /**
     * @param array<string, string> $server
     */
    private function rename(string $filename, string $newName, array $server = []): Response
    {
        $fieldFactory = App::instance()->getService(FieldFactory::class);

        $modal = $this->createStub(Modal::class);
        $modal->method('fields')->willReturn(new FieldCollection(['filename' => $fieldFactory->make('renameFileModal.filename', ['type' => 'text', 'required' => true])]));

        $modals = $this->createStub(Modals::class);
        $modals->method('get')->willReturn($modal);

        $controller = $this->controller(
            input: ['renameFileModal' => ['filename' => $newName]],
            server: $server,
            services: [Modals::class => $modals],
        );

        return $controller->rename($this->params($filename), $this->fileFactory());
    }

    /**
     * @param array<string, bool>   $permissions
     * @param array<string, mixed>  $input
     * @param array<string, string> $server
     * @param array<string, object> $services
     * @param array<string, mixed>  $files
     */
    private function controller(array $permissions = ['panel.files' => true], array $input = [], array $server = [], array $services = [], array $files = []): FilesController
    {
        return $this->makeController(
            FilesController::class,
            $permissions,
            input: $input,
            services: [Site::class => $this->site, ...$services],
            server: $server,
            files: $files,
        );
    }
}
