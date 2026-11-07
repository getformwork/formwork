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
use Formwork\Http\Response;
use Formwork\Pages\ContentFile;
use Formwork\Pages\Page;
use Formwork\Pages\PageCollection;
use Formwork\Pages\PaginationFactory;
use Formwork\Panel\Controllers\FilesController;
use Formwork\Panel\Modals\Modal;
use Formwork\Panel\Modals\Modals;
use Formwork\Router\RouteParams;
use Formwork\Router\Router;
use Formwork\Schemes\Schemes;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\BuildsPanelControllers;
use Formwork\Tests\Unit\Panel\BuildsUploads;
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
    use BuildsUploads;

    private string $contentPath;

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

        $page = $this->createStub(Page::class);
        $page->method('route')->willReturn('/about/');
        $page->method('getModelIdentifier')->willReturn('page');
        $page->method('contentPath')->willReturn($this->contentPath);
        $page->method('files')->willReturnCallback(fn(): FileCollection => $this->files());
        $page->method('contentFile')->willReturn(null);

        $descendants = new PageCollection(['/about/' => $page], new PaginationFactory(App::instance(), $this->createStub(Router::class)));

        $this->site = $this->createStub(Site::class);
        $this->site->method('findPage')->willReturnCallback(fn(string $route): ?Page => $route === '/about/' ? $page : null);
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

    public function testListReturnsTheFilesOfTheModel(): void
    {
        $this->assertOutcome(['status' => 404], $this->call($this->controller(), 'list', ['model' => 'page', 'id' => '/missing/']));

        $data = $this->outcome($this->call($this->controller(), 'list', ['model' => 'page', 'id' => '/about/']))['json']['data'];

        $this->assertSame(['a.txt', 'b.txt', 'c.txt'], array_column($data, 'filename'));
        $this->assertSame(['1 B', '3 B', '3 B'], array_column($data, 'size'));
        $this->assertSame(['info', 'rename', 'replace', 'delete'], array_keys($data[0]['actions']));
        $this->assertNull($data[0]['thumbnail']);
    }

    /**
     * @param array<string, string> $server
     * @param array<string, mixed>  $expected
     * @param array<string, bool>   $paths    Whether the paths (relative to the page) must exist after the action
     */
    #[DataProvider('actionOutcomeProvider')]
    public function testActionsRespondAsExpected(string $action, string $filename, array $server, array $expected, array $paths = []): void
    {
        $response = $this->call($this->controller(server: $server), $action, ['model' => 'page', 'id' => '/about/', 'filename' => $filename]);

        $this->assertOutcome($expected, $response);
        $this->assertPaths($paths);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function actionOutcomeProvider(): iterable
    {
        $get = ['REQUEST_METHOD' => 'GET'];
        $ajax = ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];

        yield 'edit of an unknown file' => ['edit', 'missing.txt', [], ['notices' => ['error']]];
        yield 'index' => ['index', 'b.txt', [], ['status' => 200]];
        yield 'edit' => ['edit', 'b.txt', $get, ['status' => 200]];
        yield 'delete' => ['delete', 'b.txt', [], ['notices' => ['success']], ['b.txt' => false, 'a.txt' => true]];
        yield 'delete with a script request' => ['delete', 'b.txt', $ajax, ['status' => 200], ['b.txt' => false]];
        yield 'delete of an unknown file' => ['delete', 'missing.txt', [], ['notices' => ['error']]];
        yield 'delete of an unknown file with a script request' => ['delete', 'missing.txt', $ajax, ['status' => 500]];
        yield 'replace of an unknown file' => ['replace', 'missing.txt', [], ['notices' => ['error']]];
        yield 'replace of an unknown file with a script request' => ['replace', 'missing.txt', $ajax, ['status' => 500]];
        yield 'replace without uploads only updates the modified time' => ['replace', 'b.txt', [], ['notices' => ['success']], ['b.txt' => true]];
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

        $this->call($this->controller(services: [Site::class => $site]), 'delete', $this->params('b.txt'));

        clearstatcache();
        $this->assertGreaterThan(1000, filemtime($this->contentPath . '/page.md'));
    }

    /**
     * @param array<string, string> $server
     * @param array<string, mixed>  $expected
     * @param array<string, bool>   $paths
     */
    #[DataProvider('renameProvider')]
    public function testFilesAreRenamed(string $filename, string $newName, array $server, array $expected, array $paths): void
    {
        $this->assertOutcome($expected, $this->rename($filename, $newName, $server));
        $this->assertPaths($paths);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function renameProvider(): iterable
    {
        $ajax = ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];

        yield 'to a new name' => ['b.txt', 'New name', [], ['notices' => ['success']], ['new-name.txt' => true, 'b.txt' => false]];
        yield 'keeps the extension' => ['b.txt', 'Report.pdf', [], [], ['report.txt' => true]];
        yield 'to the same name' => ['b.txt', 'b', [], [], ['b.txt' => true]];
        yield 'to an existing name' => ['b.txt', 'c', [], ['notices' => ['error']], ['b.txt' => true, 'c.txt' => true]];
        yield 'with a script request' => ['b.txt', 'New name', $ajax, ['json' => ['data' => ['filename' => 'new-name.txt', 'size' => '3 B']]], ['new-name.txt' => true]];
        yield 'of an unknown file with a script request' => ['missing.txt', 'New name', $ajax, ['status' => 500], []];
        yield 'with an invalid form' => ['b.txt', '', [], ['notices' => ['error']], ['b.txt' => true]];
        yield 'with an invalid form and a script request' => ['b.txt', '', $ajax, ['status' => 400], ['b.txt' => true]];
    }

    public function testMetadataIsDeletedWithTheFileAndInvalidNamesAreRejected(): void
    {
        $this->call($this->controller(), 'delete', $this->params('a.txt'));
        $this->assertPaths(['a.txt' => false, 'a.txt.meta.yaml' => false]);

        $this->rename('b.txt', '!!!');
        $this->assertPaths(['b.txt' => true, '.txt' => false]);
    }

    public function testRenamingKeepsTheMetadataOfTheFile(): void
    {
        $this->rename('a.txt', 'Letter');

        $this->assertPaths(['letter.txt' => true, 'letter.txt.meta.yaml' => true, 'a.txt.meta.yaml' => false]);
    }

    /**
     * @param list<int|string>      $uploads  Kinds of the uploaded files: `png`, `text`, or a PHP error code
     * @param array<string, string> $server
     * @param array<string, mixed>  $expected
     */
    #[DataProvider('replaceProvider')]
    public function testFilesAreReplaced(string $filename, array $uploads, array $server, array $expected, string $content): void
    {
        $this->assertOutcome($expected, $this->replace($filename, $this->uploadedFiles($uploads, 'file', 'new.txt'), $server));
        $this->assertSame($content, FileSystem::read($this->contentPath . '/' . $filename));
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function replaceProvider(): iterable
    {
        $ajax = ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];

        yield 'with a file of another type' => ['b.txt', ['png'], [], ['notices' => ['error']], 'bee'];
        yield 'with more than one file' => ['b.txt', ['text', 'text'], [], ['notices' => ['error']], 'bee'];
        yield 'with a file' => ['b.txt', ['text'], [], ['notices' => ['success']], 'plain text'];
        yield 'with a file and a script request' => ['b.txt', ['text'], $ajax, ['status' => 200, 'json' => ['data' => ['filename' => 'b.txt']]], 'plain text'];

        foreach (self::uploadErrorProvider() as $name => [$error]) {
            yield "with a file failing with a PHP error ($name)" => ['b.txt', [$error], [], ['notices' => ['error']], 'bee'];
            yield "with a file failing with a PHP error ($name) and a script request" => ['b.txt', [$error], $ajax, ['status' => 500], 'bee'];
        }
    }

    #[DataProvider('uploadErrorProvider')]
    public function testFailedReplacementsReportTheErrorOfTheUpload(int $error, string $languageString): void
    {
        $response = $this->replace('b.txt', [$this->failedUpload($error, 'file', 'new.txt')], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        $this->assertOutcome(['status' => 500, 'json' => ['message' => $this->uploadErrorText($languageString)]], $response);
    }

    public function testReplacingKeepsTheMetadataOfTheFile(): void
    {
        $this->replace('a.txt', [$this->uploadedText('file', 'new.txt')]);

        $this->assertSame("title: Letter A\n", FileSystem::read($this->contentPath . '/a.txt.meta.yaml'));
    }

    /**
     * @param list<int|string>     $uploads
     * @param array<string, mixed> $expected
     * @param array<string, bool>  $paths
     */
    #[DataProvider('uploadProvider')]
    public function testUploadingToTheSite(array $uploads, array $expected, array $paths): void
    {
        $response = $this->upload(['parent' => '.'], $this->uploadedFiles($uploads), TESTS_TMP_PATH . '/site-files');

        $this->assertOutcome($expected, $response);
        $this->assertPaths($paths, TESTS_TMP_PATH . '/site-files');
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function uploadProvider(): iterable
    {
        yield 'a file' => [['png'], ['notices' => ['success']], ['photo.png' => true]];
        yield 'several files' => [['png', 'png2'], ['notices' => ['success']], ['photo.png' => true, 'second.png' => true]];
        yield 'a file of a disallowed type' => [['text'], ['notices' => ['error']], ['notes.txt' => false]];
        yield 'no files' => [[], ['notices' => ['error']], []];

        foreach (self::uploadErrorProvider() as $name => [$error]) {
            yield "a file failing with a PHP error ($name)" => [[$error], ['notices' => ['error']], ['big.png' => false]];
        }
    }

    public function testExistingFilesAreNotOverwrittenByUploads(): void
    {
        $destination = TESTS_TMP_PATH . '/site-files';

        $this->upload(['parent' => '.'], [$this->uploadedImage('files')], $destination);
        $original = FileSystem::read($destination . '/photo.png');

        $this->assertOutcome(['notices' => ['success', 'error']], $this->upload(['parent' => '.'], [$this->uploadedImage('files')], $destination));
        $this->assertSame($original, FileSystem::read($destination . '/photo.png'));
    }

    /**
     * @param array<string, bool> $paths
     */
    private function assertPaths(array $paths, ?string $base = null): void
    {
        foreach ($paths as $path => $exists) {
            $exists ? $this->assertFileExists(($base ?? $this->contentPath) . '/' . $path) : $this->assertFileDoesNotExist(($base ?? $this->contentPath) . '/' . $path);
        }
    }

    /**
     * @param list<UploadedFile>    $files
     * @param array<string, string> $server
     */
    private function replace(string $filename, array $files, array $server = []): Response
    {
        return $this->controller(services: [FileUploader::class => $this->uploader([$this->contentPath])], server: $server, files: ['file' => $files])
            ->replace(new RouteParams($this->params($filename)), $this->uploader([$this->contentPath]));
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
            system: ['files' => ['paths' => ['site' => $destination]]],
            input: ['uploadFileModal' => $input],
            services: [Site::class => $this->site, Modals::class => $modals, FileUploader::class => $uploader],
            server: ['REQUEST_METHOD' => 'POST'],
            files: $files === [] ? [] : ['uploadFileModal' => ['files' => $files]],
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

    /**
     * @return array<string, string>
     */
    private function params(string $filename): array
    {
        return ['model' => 'page', 'id' => '/about/', 'filename' => $filename];
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
            services: [Modals::class => $modals],
            input: ['renameFileModal' => ['filename' => $newName]],
            server: $server,
        );

        return $controller->rename(new RouteParams($this->params($filename)), $this->fileFactory());
    }

    /**
     * @param array<string, object> $services
     */
    private function controller(array $services = [], mixed ...$arguments): FilesController
    {
        return $this->makeController(FilesController::class, ...$arguments, services: [Site::class => $this->site, ...$services]);
    }
}
